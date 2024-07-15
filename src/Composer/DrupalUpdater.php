<?php

namespace DigitalPolygon\Polymer\Composer;

use Composer\Composer;
use Composer\Console\Application;
use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use Composer\Semver\Constraint\Constraint;
use Composer\Semver\VersionParser;
use Composer\Util\Filesystem;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This class updates Drupal core to the next available stable version using Composer.
 *
 * This class performs the following operations:
 * 1. Determines the current stable version of Drupal core.
 * 2. Determines the next available stable version of Drupal core.
 * 3. Updates the composer.json file to set the next stable version for
 *    drupal/core-recommended, drupal/core-composer-scaffold, and drupal/core-dev.
 * 4. Sets the version constraints for all other required and required-dev packages to '*'.
 * 5. Runs 'composer update --minimal-changes' to update the packages with minimal changes.
 * 6. Replaces wildcard versions in composer.json with caret versions from composer.lock.
 * 7. Updates composer.lock hashes.
 */
final class DrupalUpdater
{
    /**
     * The Composer service instance.
     *
     * @var \Composer\Composer
     */
    private Composer $composer;

    /**
     * The Composer Console Application instance.
     *
     * @var \Composer\Console\Application
     */
    private Application $application;

    /**
     * Input service for Composer Application.
     *
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    private InputInterface $input;

    /**
     * Output service for Composer Application.
     *
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    private OutputInterface $output;

    /**
     * I/O service for Composer.
     *
     * @var \Composer\IO\IOInterface
     */
    protected IOInterface $io;

    /**
     * Represents the composer.json file.
     *
     * @var \Composer\Json\JsonFile
     */
    private JsonFile $composerJson;

    /**
     * Represents the composer.lock file.
     *
     * @var \Composer\Json\JsonFile
     */
    private JsonFile $composerLock;

    /**
     * Stores the original content of composer.json before modifications.
     *
     * @var string
     */
    private string $composerJsonBackup;

    /**
     * Stores the original content of composer.lock before modifications.
     *
     * @var string
     */
    private string $composerLockBackup;

    /**
     * DrupalUpdater constructor.
     *
     * @param Composer $composer
     *   The Composer service instance.
     * @param Application $application
     *   The Composer Console Application instance.
     * @param InputInterface $input
     *   The Input service for Composer Application.
     * @param OutputInterface $output
     *   The Output service for Composer Application.
     * @param IOInterface $io
     *   The I/O service for Composer.
     */
    public function __construct(Composer $composer, Application $application, InputInterface $input, OutputInterface $output, IOInterface $io)
    {
        $this->composer = $composer;
        $this->application = $application;
        $this->input = $input;
        $this->output = $output;
        $this->io = $io;
    }

    /**
     * Executes the Drupal core update process.
     *
     * @return int
     *   Returns 0 if successful, otherwise an error code.
     */
    public function execute(): int
    {
        // Attempt to read composer files.
        if (!$this->readComposerFiles()) {
            return 1;
        }
        // Retrieve current and next stable versions of Drupal core.
        $current_version = $this->getCurrentVersion();
        if ($current_version === null) {
            $this->io->writeError("Unable to determine the current Drupal core version. Ensure this is a valid Drupal project.");
            return 0;
        }
        $next_stable_version = $this->getNextStableVersion($current_version);
        // Validate if update is necessary.
        if ($next_stable_version === null) {
            $this->io->write("Your Drupal core ($current_version) is already the latest stable version available.");
            return 0;
        }
        // Confirm upgrade with user.
        if (!$this->input->getOption('yes') && !$this->confirmUpgrade($current_version, $next_stable_version)) {
            $this->io->write("Operation cancelled by user.");
            return 0;
        }
        // Backup composer files before modification.
        $this->backupComposerFiles();
       // Try to upgrade.
        try {
            // Start Drupal core update process.
            $this->io->write("<info>Updating Drupal core from version $current_version to version $next_stable_version.</info>");
            // Step 1: Update composer.json with next stable versions and wildcards.
            $this->updateComposerJsonWithWildcards($next_stable_version);
            // Step 2: Run 'composer update --minimal-changes' to update with minimal changes.
            $this->runComposerUpdate(['--minimal-changes' => true, '--no-interaction' => true]);
            // Step 3: Replace wildcard versions in composer.json with caret versions.
            $this->replaceWildcardVersionsInComposerJson();
            $this->runComposerUpdate(['--lock' => true, '--no-interaction' => true]);
            // Completion message.
            $this->io->write("<info>Drupal core has been successfully updated from version $current_version to version $next_stable_version.</info>");
            return 0;
        } catch (\Exception $e) {
            // Error handling: revert changes and return error code.
            $this->io->writeError("<error>Exception caught: {$e->getMessage()}</error>");
            $this->revertComposerFiles();
            return 1;
        }
    }

    /**
     * Reads the composer.json and composer.lock files.
     *
     * @return bool
     *   Returns TRUE if files are readable, otherwise FALSE.
     */
    private function readComposerFiles(): bool
    {
        // Determine paths for composer.json and composer.lock.
        $composer_json_path = Factory::getComposerFile();
        $composer_lock_path = Factory::getLockFile($composer_json_path);
        // Check readability of files.
        if (!Filesystem::isReadable($composer_json_path)) {
            $this->io->writeError('<error>' . $composer_json_path . ' is not readable.</error>');
            return false;
        }
        if (!Filesystem::isReadable($composer_lock_path)) {
            $this->io->writeError('<error>' . $composer_lock_path  . ' is not readable.</error>');
            return false;
        }
        // Initialize JsonFile objects.
        $this->composerJson = new JsonFile($composer_json_path);
        $this->composerLock = new JsonFile($composer_lock_path);
        return true;
    }

    /**
     * Backup composer.json and composer.lock files before any modifications.
     */
    private function backupComposerFiles(): void
    {
        $this->io->write("Backing up composer.json and composer.lock files...");
        // Backup composer.json.
        /** @var string $json_raw_content */
        $json_raw_content = file_get_contents($this->composerJson->getPath());
        $this->composerJsonBackup = $json_raw_content;
        // Backup composer.lock.
        /** @var string $lock_raw_content */
        $lock_raw_content = file_get_contents($this->composerLock->getPath());
        $this->composerLockBackup = $lock_raw_content;
    }

    /**
     * Retrieves the current version of Drupal core.
     *
     * @return string|null
     *   Returns the current version of Drupal core.
     */
    private function getCurrentVersion(): ?string
    {
        $installedRepo = $this->composer->getRepositoryManager()->getLocalRepository();
        $package = $installedRepo->findPackage('drupal/core-recommended', '*');
        if ($package == null) {
            return null;
        }
        return $package->getPrettyVersion();
    }

    /**
     * Retrieves the next stable version of Drupal core.
     *
     * @param string $current_version
     *   The current version of Drupal core.
     *
     * @return string|null
     *   Returns the next stable version of Drupal core, or NULL if none is found.
     */
    private function getNextStableVersion(string $current_version): ?string
    {
        $available_versions = $this->getAvailableVersions('drupal/core-recommended');
        foreach ($available_versions as $version) {
            if ($this->isStableVersion($version) && version_compare($version, $current_version, '>')) {
                return $version;
            }
        }
        return null;
    }

    /**
     * Retrieves available versions of a package from Composer repositories.
     *
     * @param string $package_name
     *   The name of the package.
     *
     * @return array<string, string>
     *   Returns an array of available versions.
     */
    private function getAvailableVersions(string $package_name): array
    {
        $repositoryManager = $this->composer->getRepositoryManager();
        $packages = $repositoryManager->findPackages($package_name, '*');
        $versions = [];
        foreach ($packages as $package) {
            $version = $package->getPrettyVersion();
            $versions[$version] = $version;
        }
        return $versions;
    }

    /**
     * Prompts the user for confirmation before proceeding with the upgrade.
     *
     * @param string $current_version
     *   The current version of Drupal.
     * @param string $next_version
     *   The next stable version of Drupal.
     *
     * @return bool
     *   TRUE if the user confirms the upgrade, FALSE otherwise.
     */
    private function confirmUpgrade(string $current_version, string $next_version): bool
    {
        $this->io->write("Your current Drupal version is \"$current_version\" and the next available version is \"$next_version\".");
        return $this->io->askConfirmation("Do you want to proceed with the upgrade? (yes/no)", false);
    }

    /**
     * Update composer.json with wildcard versions for core packages and '*' for other packages.
     *
     * @param string $next_stable_version
     *   The next stable version of Drupal.
     */
    private function updateComposerJsonWithWildcards(string $next_stable_version): void
    {
        // Read composer.json content.
        /** @var array<string, string> $composer_json */
        $composer_json = $this->composerJson->read();
        // Define core packages to update with specific version.
        $core_packages = ['drupal/core-recommended', 'drupal/core-composer-scaffold', 'drupal/core-dev'];
        // Update version for core packages.
        foreach ($core_packages as $package) {
            $composer_json['require'][$package] = $next_stable_version;
        }
        // Set wildcard version constraint for all other required packages.
        foreach ($composer_json['require'] as $package => $version) {
            if (!in_array($package, $core_packages)) {
                $composer_json['require'][$package] = '*';
            }
        }
        // Set wildcard version constraint for all other required-dev packages.
        if (isset($composer_json['require-dev'])) {
            foreach ($composer_json['require-dev'] as $package => $version) {
                if (!in_array($package, $core_packages)) {
                    $composer_json['require-dev'][$package] = '*';
                }
            }
        }
        // Save updated composer.json content.
        $this->composerJson->write($composer_json);
    }

    /**
     * Replaces wildcard versions in composer.json with caret versions from composer.lock.
     */
    private function replaceWildcardVersionsInComposerJson(): void
    {
        // Read composer.json file.
        /** @var array<string, array<string, string>> $json_data */
        $json_data = $this->composerJson->read();
        // Extract locked versions from composer.lock.
        $locked_versions = $this->extractLockedVersions();
        // Update wildcard versions for all other required packages in composer.json.
        foreach ($json_data['require'] as $package => $version) {
            if ($this->hasWildcardVersion($version) && isset($locked_versions[$package])) {
                $exact_wersion = $locked_versions[$package];
                $caret_version = $this->generateCaretVersion($exact_wersion);
                $json_data['require'][$package] = $caret_version;
            }
        }
        // Update wildcard versions for all other required-dev packages in composer.json.
        if (isset($json_data['require-dev'])) {
            foreach ($json_data['require-dev'] as $package => $version) {
                if ($this->hasWildcardVersion($version) && isset($locked_versions[$package])) {
                    $exact_wersion = $locked_versions[$package];
                    $caret_version = $this->generateCaretVersion($exact_wersion);
                    $json_data['require-dev'][$package] = $caret_version;
                }
            }
        }
        // Write back the updated composer.json.
        $this->composerJson->write($json_data);
    }

    /**
     * Extracts locked versions from composer.lock file.
     *
     * @return array<string, string>
     *   An associative array where keys are package names and values are versions.
     */
    private function extractLockedVersions(): array
    {
        // Reads data from composer.lock file.
        /** @var array<string, array<string, string>> $lock_data */
        $lock_data = $this->composerLock->read();
        $locked_versions = [];
        /** @var array<string, string> $package */
        foreach ($lock_data['packages'] as $package) {
            $locked_versions[$package['name']] = $package['version'];
        }
        return $locked_versions;
    }

    /**
     * Checks if a version string contains a wildcard (*).
     *
     * @param string $version
     *   The version string to check.
     *
     * @return bool
     *   TRUE if the version contains a wildcard, otherwise FALSE.
     */
    private function hasWildcardVersion(string $version): bool
    {
        $version_parser = new VersionParser();
        $constraint = $version_parser->parseConstraints($version);
        // Create a constraint to match against '*'.
        $wildcard_constraint = new Constraint('=', '*');
        return $constraint->matches($wildcard_constraint);
    }

    /**
     * Generates a caret version (^) based on the given version.
     *
     * @param string $version
     *   The version string.
     *
     * @return string
     *   The caret version.
     */
    private function generateCaretVersion(string $version): string
    {
        $version_parser = new VersionParser();
        $normalized = $version_parser->normalize($version);
        $parts = explode('.', $normalized);
        // Ensure there are at least two parts (major and minor versions).
        if (count($parts) >= 2) {
            return '^' . $parts[0] . '.' . $parts[1];
        }
        // Default to returning the original version prefixed with '^'.
        return '^' . $version;
    }

    /**
     * Checks if a version string represents a stable release.
     *
     * @param string $version
     *   The version string to check.
     *
     * @return bool
     *   TRUE if the version is stable, FALSE otherwise.
     */
    private function isStableVersion(string $version): bool
    {
        $version_parser = new VersionParser();
        return $version_parser->parseStability($version) === 'stable';
    }

    /**
     * Runs 'composer update' command with specific flags.
     *
     * @param array<string, bool> $options
     *   The flags to pass to 'composer update' command.
     */
    private function runComposerUpdate(array $options): void
    {
        $update_command = $this->application->find('update');
        $this->application->resetComposer();
        // Run composer update and capture the exit code.
        $input = new ArrayInput($options);
        $exit_code = $update_command->run($input, $this->output);
        // Check for errors.
        if ($exit_code !== 0) {
            throw new \RuntimeException("Failed to run 'composer update', Could not update dependencies.");
        } else {
            $this->io->write('<info>Composer update completed successfully.</info>');
        }
    }

    /**
     * Reverts composer.json and composer.lock files to their original state.
     */
    private function revertComposerFiles(): void
    {
        $this->io->write("Reverting composer.json and composer.lock files...");
        file_put_contents($this->composerJson->getPath(), $this->composerJsonBackup);
        file_put_contents($this->composerLock->getPath(), $this->composerLockBackup);
        $this->io->write("Composer files reverted successfully.");
        $this->io->write("To restore the vendor folder to its previous state, please run <info>composer install</info>.");
    }
}
