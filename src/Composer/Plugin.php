<?php

namespace DigitalPolygon\Polymer\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Util\Filesystem;
use Composer\Script\ScriptEvents;
use Composer\Util\ProcessExecutor;
use Composer\Installer\PackageEvent;
use Composer\Plugin\PluginInterface;
use Composer\Installer\PackageEvents;
use Composer\Package\PackageInterface;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\DependencyResolver\Operation\InstallOperation;

class Plugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * Process.
     *
     * @var ProcessExecutor
     */
    protected $executor;

    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * The Polymer package.
     *
     * @var mixed
     */
    private mixed $polymerPackage = null;

    /**
     * {@inheritdoc}
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
        $this->executor = new ProcessExecutor($this->io);
    }

    /**
     * {@inheritdoc}
     */
    public function deactivate(Composer $composer, IOInterface $io)
    {
        // TODO: Implement deactivate() method.
        $x = 5;
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(Composer $composer, IOInterface $io)
    {
        // TODO: Implement uninstall() method.
        $x = 5;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     */
    public static function getSubscribedEvents()
    {
        return [
            PackageEvents::POST_PACKAGE_INSTALL => "onPostPackageEvent",
            ScriptEvents::POST_INSTALL_CMD => "onPostCmdEvent",
        ];
    }

    /**
     * Gets the digitalpolygon/polymer package, if it is the package being operated on.
     *
     * @param mixed $operation
     *   Op.
     *
     * @return mixed
     *   Mixed.
     */
    protected function getPolymerPackage($operation): mixed
    {
        if ($operation instanceof InstallOperation) {
            $package = $operation->getPackage();
        } elseif ($operation instanceof UpdateOperation) {
            $package = $operation->getTargetPackage();
        }
        if (isset($package) && $package instanceof PackageInterface && $package->getName() == 'digitalpolygon/polymer') {
            return $package;
        }
        return null;
    }

    /**
     * Marks digitalpolygon/polymer to be processed after an install or update command.
     *
     * @param \Composer\Installer\PackageEvent $event
     *   Event.
     */
    public function onPostPackageEvent(PackageEvent $event): void
    {
        $package = $this->getPolymerPackage($event->getOperation());
        if ($package) {
            // By explicitly setting the polymer package, the onPostCmdEvent() will
            // process the update automatically.
            $this->polymerPackage = $package;
        }
    }

    /**
     * Execute polymer polymer:update after update command has been executed.
     *
     * @throws \Exception
     */
    public function onPostCmdEvent(): void
    {
        // Only install the template files if digitalpolygon/polymer is installed.
        if (isset($this->polymerPackage)) {
            $this->executePolymerUpdate();
        }
    }

    /**
     * Create a new directory.
     *
     * @param string $path
     *   Path to create.
     *
     * @return bool
     *   TRUE if directory exists or is created.
     */
    protected function createDirectory(string $path): bool
    {
        return is_dir($path) || mkdir($path);
    }

    /**
     * Returns the repo root's filepath, assumed to be one dir above vendor dir.
     *
     * @return string
     *   The file path of the repository root.
     */
    public function getRepoRoot(): string
    {
        return dirname($this->getVendorPath());
    }

    /**
     * Get the path to the 'vendor' directory.
     *
     * @return string
     *   String.
     */
    public function getVendorPath(): string
    {
        $config = $this->composer->getConfig();
        $filesystem = new Filesystem();
        $filesystem->ensureDirectoryExists($config->get('vendor-dir'));

        /** @var string $realpath */
        $realpath = realpath($config->get('vendor-dir'));
        return $filesystem->normalizePath($realpath);
    }

    /**
     * Determine if Polymer is being installed for the first time on this project.
     *
     * @return bool
     *   TRUE if this is the initial install of Polymer.
     */
    protected function isInitialInstall(): bool
    {
        if (!file_exists($this->getRepoRoot() . '/polymer/polymer.yml')) {
            return true;
        }

        return false;
    }

    /**
     * Executes `polymer polymer:update` and `polymer-console polymer:update` commands.
     *
     * @throws \Exception
     */
    protected function executePolymerUpdate(): void
    {
        if ($this->isInitialInstall()) {
            $this->io->write('<info>Creating Polymer template files...</info>');
            /** @var string $command */
            $command = $this->getVendorPath() . '/bin/polymer polymer:init';
            $success = $this->executeCommand($command, [], true);
            if (!$success) {
                $this->io->writeError("<error>Polymer installation failed! Please execute <comment>$command --verbose</comment> to debug the issue.</error>");
                throw new \Exception('Installation aborted due to error');
            }
        }
    }

    /**
     * Executes a shell command with escaping.
     *
     * Example usage: $this->executeCommand("test command %s", [ $value ]).
     *
     * @param string $cmd
     *   Cmd.
     * @param array<int, string> $args
     *   Args.
     * @param bool $display_output
     *   Optional. Defaults to FALSE. If TRUE, command output will be displayed
     *   on screen.
     *
     * @return bool
     *   TRUE if command returns successfully with a 0 exit code.
     */
    protected function executeCommand(string $cmd, array $args = [], $display_output = false): bool
    {
        // Shell-escape all arguments.
        foreach ($args as $index => $arg) {
            $args[$index] = escapeshellarg($arg);
        }
        // Add command as first arg.
        array_unshift($args, $cmd);
        // And replace the arguments.
        /** @var string $command */
        $command = call_user_func_array('sprintf', $args);
        $output = '';
        if ($this->io->isVerbose() || $display_output) {
            $this->io->write('<comment> > ' . $command . '</comment>');
            $io = $this->io;
            $output = function ($type, $buffer) use ($io) {
                $io->write($buffer, false);
            };
        }
        return ($this->executor->execute($command, $output) == 0);
    }
}
