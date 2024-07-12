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
 * 6. Replaces wildcard versions in composer.json with caret versions from composer.lock.
 * 7. Updates composer.lock hashes.
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
 * Reads and decodes a JSON file.
 *
 * @param string $path
 *   The path to the JSON file.
 *
 * @return array<string, array<string, string>>
 *   The decoded JSON data as an associative array.
 */
function read_json_file(string $path): array
{
    /** @var string $raw_content */
    $raw_content = file_get_contents($path);
    /** @var array<string, array<string, string>> $data */
    $data = json_decode($raw_content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "Error reading $path file\n";
        return [];
    }
    return $data;
}

/**
 * Writes data to a JSON file.
 *
 * @param string $path
 *   The path to the JSON file.
 * @param array<string, array<string, string>> $data
 *   The data to write to the file.
 *
 * @return bool
 *   TRUE if the data was successfully written to the file, otherwise FALSE.
 */
function write_json_file(string $path, array $data): bool
{
    $modified_content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    $result = file_put_contents($path, $modified_content);
    if ($result === false) {
        echo "Error writing to $path file\n";
        return false;
    }
    return true;
}

/**
 * Extracts locked versions from composer.lock.
 *
 * @param array<string, array<string, string>> $lock_data
 *   The decoded 'composer.lock' data.
 *
 * @return array<string, string>
 *   An associative array where keys are package names and values are versions.
 */
function extract_locked_versions(array $lock_data): array
{
    $locked_versions = [];
    /** @var array<string, array<string, string>> $packages */
    $packages = $lock_data['packages'];
    foreach ($packages as $package) {
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
function has_wildcard_version(string $version): bool
{
    return strpos($version, '*') !== false;
}

/**
 * Generates a caret version constraint based on the exact version.
 *
 * @param string $version
 *   The exact version string.
 *
 * @return string
 *   The caret version constraint string.
 */
function generate_caret_version(string $version): string
{
    // Extract major and minor version parts.
    $parts = explode('.', $version);
    if (count($parts) >= 2) {
        return '^' . $parts[0] . '.' . $parts[1];
    }
    return '^' . $version;
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
 * @param string $composer_json_path
 *   The path to the composer.json file.
 */
function update_composer_json_with_wildcards(string $next_stable_version, string $composer_json_path): void
{
    // Read composer.json content.
    /** @var array<string, string> $composer_json */
    $composer_json = read_json_file($composer_json_path);

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
    write_json_file($composer_json_path, $composer_json);
}

/**
 * Replaces wildcard versions in composer.json with caret versions from composer.lock.
 *
 * This function reads the composer.lock file to get the exact versions of the packages
 * and then updates the composer.json file to replace wildcard version constraints with
 * caret version constraints based on those exact versions.
 *
 * @param string $composer_json_path
 *   The path to the composer.json file.
 * @param string $composer_lock_path
 *   The path to the composer.lock file.
 */
function replace_wildcard_versions_in_composer_json(string $composer_json_path, string $composer_lock_path): void
{
    // Read composer.lock and composer.json files.
    $lock_data = read_json_file($composer_lock_path);
    $json_data = read_json_file($composer_json_path);
    if (empty($lock_data) || empty($json_data)) {
        return;
    }

    // Extract locked versions from composer.lock.
    $locked_versions = extract_locked_versions($lock_data);

    // Update wildcard versions for all other required packages in composer.json.
    foreach ($json_data['require'] as $package => $version) {
        if (has_wildcard_version($version) && isset($locked_versions[$package])) {
            $exact_version = $locked_versions[$package];
            $caret_version = generate_caret_version($exact_version);
            $json_data['require'][$package] = $caret_version;
        }
    }

    // Update wildcard versions for all other required-dev packages in composer.json.
    if (isset($json_data['require-dev'])) {
        foreach ($json_data['require-dev'] as $package => $version) {
            if (has_wildcard_version($version) && isset($locked_versions[$package])) {
                $exact_version = $locked_versions[$package];
                $caret_version = generate_caret_version($exact_version);
                $json_data['require-dev'][$package] = $caret_version;
            }
        }
    }

    // Write back the updated composer.json.
    if (write_json_file($composer_json_path, $json_data)) {
        echo "composer.json has been updated with caret versions from composer.lock\n";
    }
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
// Define the path to the composer files.
$composer_json_path = 'composer.json';
$composer_lock_path = 'composer.lock';

// Update composer.json with the next stable version.
update_composer_json_with_wildcards($next_stable_version, $composer_json_path);

// Perform composer update with minimal changes.
echo "Updating Drupal core to version $next_stable_version...\n";
shell_exec('composer update --minimal-changes');

// Replaces wildcard versions in composer.json with caret versions from composer.lock.
replace_wildcard_versions_in_composer_json($composer_json_path, $composer_lock_path);
// Update composer.lock hashed.
shell_exec('composer update --lock');

echo "Drupal core has been successfully updated to version $next_stable_version.\n";
