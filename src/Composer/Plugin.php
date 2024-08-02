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
    public function activate(Composer $composer, IOInterface $io, ProcessExecutor $execeutor)
    {
        $this->composer = $composer;
        $this->io = $io;
        $this->executor = $execeutor;
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
   */
    public static function getSubscribedEvents()
    {
        return [
            PackageEvents::POST_PACKAGE_INSTALL => "onPostPackageEvent",
            PackageEvents::POST_PACKAGE_UPDATE => "onPostPackageEvent",
            ScriptEvents::POST_UPDATE_CMD => "onPostCmdEvent",
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
    public function getRepoRoot()
    {
        return dirname($this->getVendorPath());
    }

    /**
     * Get the path to the 'vendor' directory.
     *
     * @return string
     *   String.
     */
    public function getVendorPath()
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
            $this->io->write('<comment> > ' . $command . '</comment>');
            $success = $this->executor->execute($command);
            if (!$success) {
                $this->io->writeError("<error>Polymer installation failed! Please execute <comment>$command --verbose</comment> to debug the issue.</error>");
                throw new \Exception('Installation aborted due to error');
            }
        }
    }
}
