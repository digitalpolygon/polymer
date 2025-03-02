<?php

/**
 * @file
 * Bootstrap Polymer.
 */

$repo_root = find_repo_root();
if (!is_string($repo_root)) {
    print "Unable to determine the Polymer root directory.\n";
    exit(1);
}
$classLoader = require_once $repo_root . '/vendor/autoload.php';
if (!isset($classLoader)) {
    print "Unable to find autoloader for Polymer\n";
    exit(1);
}

require_once __DIR__ . '/polymer-robo-run.php';

/**
 * Finds the root directory for the repository.
 *
 * Ordinarily this function is robust, but it can fail if you've symlinked Polymer
 * into your vendor directory (as with a Composer path repository) and are not
 * running commands from the project root. In this state, Polymer has no possible
 * way to identify the root directory.
 *
 * @return bool|string
 *   Root.
 */
function find_repo_root()
{
    $polymer_files = ['vendor/digitalpolygon/polymer', 'vendor/autoload.php'];
    $polymer_files = ['vendor/autoload.php'];
    $possible_repo_roots = [
        getcwd(),
        dirname(__DIR__),
        dirname(__DIR__, 3),
    ];
    // Check for PWD - some local environments will not have this key.
    if (getenv('PWD')) {
        array_unshift($possible_repo_roots, getenv('PWD'));
    }
    $possible_repo_roots = array_unique($possible_repo_roots);
    $possible_repo_roots = array_filter($possible_repo_roots, 'is_dir');
    foreach ($possible_repo_roots as $possible_repo_root) {
        if ($repo_root = find_directory_containing_files($possible_repo_root, $polymer_files)) {
            return $repo_root;
        }
    }
    return false;
}

/**
 * Traverses file system upwards in search of a given file.
 *
 * Begins searching for $file in $working_directory and climbs up directories
 * $max_height times, repeating search.
 *
 * @param string $working_directory
 *   Working directory.
 * @param array<string> $files
 *   Files.
 * @param int $max_height
 *   Max Height.
 *
 * @return bool|string
 *   FALSE if file was not found. Otherwise, the directory path containing the
 *   file.
 */
function find_directory_containing_files(string $working_directory, array $files, $max_height = 10)
{
    // Find the root directory of the git repository containing Polymer.
    // We traverse the file tree upwards $max_height times until we find $files.
    $file_path = $working_directory;
    for ($i = 0; $i <= $max_height; $i++) {
        if (files_exist($file_path, $files)) {
            return $file_path;
        } else {
            $file_path = realpath($file_path . '/..');
            if (!$file_path) {
                // realpath() returns false if the directory does not exist.
                break;
            }
        }
    }

    return false;
}

/**
 * Determines if an array of files exist in a particular directory.
 *
 * @param string $dir
 *   Dir.
 * @param array<string> $files
 *   Files.
 *
 * @return bool
 *   Exists.
 */
function files_exist(string $dir, array $files)
{
    foreach ($files as $file) {
        if (!file_exists($dir . '/' . $file)) {
            return false;
        }
    }
    return true;
}
