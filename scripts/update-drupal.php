#!/usr/bin/env php
<?php

/**
 * @file
 * This script updates Drupal core to the next available stable version.
 *
 * This script performs the following operations:
 * 1. Determines the current stable version of Drupal core.
 * 2. Determines the next available stable version of Drupal core.
 * 3. Updates the composer.json file to set the next stable version for
 *    drupal/core-recommended, drupal/core-composer-scaffold, and drupal/core-dev.
 * 4. Sets the version constraints for all other required and required-dev packages to '*'.
 * 5. Runs 'composer update --minimal-changes' to update the packages with minimal changes.
 *
 * Requirements:
 * - PHP CLI must be installed on your system.
 * - Composer must be installed and accessible via the command line.
 *
 * Usage:
 *   1. Save this script to a file, e.g., update-drupal.php.
 *   2. Make the script executable: chmod +x update-drupal.php
 *   3. Run the script: ./update-drupal.php
 *
 * Example:
 *   To update Drupal core to the next available stable version, execute the script as follows:
 *   ./update-drupal.php
 *   Using DDEV:
 *   - Install Drupal "10.2.7":
 *     ddev exec composer create-project drupal/recommended-project:10.2.7 test_drupal;
 *   - Upgrade Drupal from "10.2.7" to "10.3.1"
 *     ddev exec --dir=/var/www/html/test_drupal ../scripts/update-drupal.php;
 */

/**
 * Checks if the given version string represents a stable release.
 *
 * @param string $version
 *   The version string to check.
 *
 * @return bool
 *   TRUE if the version is stable (not alpha, beta, RC, etc.), FALSE otherwise.
 */
function is_stable_version(string $version): bool
{
    // Regular expression to match stable versions (not alpha, beta, RC, etc.).
    return !preg_match('/(alpha|beta|rc)/i', $version);
}

/**
 * Determines the next stable version of Drupal.
 *
 * @param string $current_version
 *   The current version of Drupal.
 *
 * @return string|null
 *   The next stable version of Drupal, or NULL if none is found.
 */
function get_next_stable_version(string $current_version): ?string
{
    // Retrieve the available versions of Drupal core from composer.
    /** @var string $json */
    $json = shell_exec('composer show drupal/core-recommended --all --format=json');
    /** @var array<string, string> $package_information */
    $package_information = json_decode($json, true);
    /** @var array<int, string> $available_versions */
    $available_versions = $package_information['versions'];

    // Sort versions in descending order (latest first).
    /** @var callable(string $a, string $b): int $comparer */
    $comparer = 'version_compare';
    usort($available_versions, $comparer);
    $available_versions = array_reverse($available_versions);

    // Find the next stable version greater than the current version.
    foreach ($available_versions as $version) {
        if (preg_match('/^\d+\.\d+.\d+$/', $version) && is_stable_version($version)) {
            if (version_compare($version, $current_version, '>')) {
                return $version;
            }
        }
    }

    // Return NULL if no newer stable version is found.
    return null;
}

/**
 * Retrieves the current version of Drupal core.
 *
 * @return string
 *   The current version of Drupal.
 */
function get_current_version(): string
{
    // Retrieve the current version of Drupal core from composer.
    /** @var string $json */
    $json = shell_exec('composer show drupal/core-recommended --format=json');
    /** @var array<string, string> $package_information */
    $package_information = json_decode($json, true);
    /** @var array<int, string> $versions */
    $versions = $package_information['versions'];
    /** @var string $version */
    $version = current($versions);
    return $version;
}

/**
 * Updates the composer.json file with the specified Drupal version.
 *
 * @param string $next_stable_version
 *   The next stable version of Drupal to set in composer.json.
 */
function update_composer_json(string $next_stable_version): void
{
    // Read composer.json content.
    $filename = 'composer.json';
    /** @var string $raw_content */
    $raw_content = file_get_contents($filename);
    /** @var array<string, string> $composer_json */
    $composer_json = json_decode($raw_content, true);

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
    $modified_content = json_encode($composer_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    file_put_contents($filename, $modified_content);
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
function confirm_upgrade(string $current_version, string $next_version): bool
{
    echo "Your current Drupal version is \"$current_version\" and the next available version is \"$next_version\".\n";
    echo "Do you want to proceed with the upgrade? (yes/no): ";

    /** @var resource $handle */
    $handle = fopen("php://stdin", "r");
    /** @var string $raw_line */
    $raw_line = fgets($handle);
    $line = trim($raw_line);
    fclose($handle);

    return strtolower($line) === 'yes';
}

// Main script execution.
$current_version = get_current_version();
$next_stable_version = get_next_stable_version($current_version);

// Check if there is a newer stable version to update to.
if ($next_stable_version === null) {
    echo "Your Drupal core ($current_version) is already the latest stable version available.\n";
    exit(0);
}

// Prompt user for confirmation before proceeding with the upgrade.
if (!confirm_upgrade($current_version, $next_stable_version)) {
    echo "Operation cancelled by user.\n";
    exit(0);
}

// Update composer.json with the next stable version.
update_composer_json($next_stable_version);

// Perform composer update with minimal changes.
echo "Updating Drupal core to version $next_stable_version...\n";
shell_exec('composer update --minimal-changes');

echo "Drupal core has been successfully updated to version $next_stable_version.\n";
