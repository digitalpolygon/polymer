#!/usr/bin/env bash

# A script to automate major Drupal version upgrades including:
# 1. Checking and installing DDEV if necessary
# 2. Updating Drupal core and contributed modules
# 3. Applying database updates
# 4. Exporting configuration files

# Exit immediately if a command exits with a non-zero status
set -e

# Function to display script usage.
show_usage() {
    echo "Usage: $0 [-p <project_path>] [-v <new_version>] [--latest-minor] [--latest-major] [--next-major]"
    echo "  -p <project_path>  : The path to the Drupal project (default: current directory)"
    echo "  -v <new_version>   : The new Drupal version to update to"
    echo "  --latest-minor     : Update to the latest stable minor version within the current major version"
    echo "  --latest-major     : Update to the latest stable major version"
    echo "  --next-major       : Update to the latest stable of the next major version"
    exit 1
}

# Function to ensure DDEV is installed, installs it if missing.
#
# Usage: ensure_ddev_installed;
ensure_ddev_installed() {
    if ! command -v ddev &> /dev/null; then
        echo "DDEV not found. Installing DDEV..."
        curl -fsSL https://apt.fury.io/drud/gpg.key | gpg --dearmor | sudo tee /etc/apt/trusted.gpg.d/ddev.gpg > /dev/null
        echo "deb [signed-by=/etc/apt/trusted.gpg.d/ddev.gpg] https://apt.fury.io/drud/ * *" | sudo tee /etc/apt/sources.list.d/ddev.list
        sudo apt update && sudo apt install -y ddev && mkcert -install
        ddev config global --instrumentation-opt-in=false --omit-containers=dba,ddev-ssh-agent
    fi
}

# Function to check if the composer plugin 'digitalpolygon/drupal-upgrade-plugin' is installed.
#
# Usage: check_plugin_installed;
check_plugin_installed() {
    ddev exec -- composer show digitalpolygon/drupal-upgrade-plugin &> /dev/null
}

# Function to install the composer plugin 'digitalpolygon/drupal-upgrade-plugin' if it's not already installed.
#
# Usage: install_plugin_if_needed;
install_plugin_if_needed() {
    if ! check_plugin_installed; then
        # "Installing composer plugin 'digitalpolygon/drupal-upgrade-plugin'..."
        ddev composer config repositories.drupal-upgrade-plugin '{"type":"vcs","url":"git@github.com:digitalpolygon/drupal-upgrade-plugin.git"}'
        ddev exec -- composer config -g github-oauth.github.com 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
        ddev exec -- composer config --no-plugins allow-plugins.digitalpolygon/drupal-upgrade-plugin true
        if ddev exec -- composer require digitalpolygon/drupal-upgrade-plugin --no-interaction; then
            echo "true" # return true to indicate plugin was installed by the script
        else
            # Error installing plugin. Exiting.
            echo "false"
            exit 1
        fi
    else
        # "Composer plugin 'digitalpolygon/drupal-upgrade-plugin' is already installed."
        echo "false" # return false to indicate plugin was not installed by the script
    fi
}

# Function to remove the 'digitalpolygon/drupal-upgrade-plugin' if it was installed by the script.
#
# Arguments:
#   $1 - A flag indicating if the plugin was installed by the script (true/false).
#
# Usage: remove_plugin_if_installed "$installed_by_script";
remove_plugin_if_installed() {
    local installed_by_script=$1
    if [ "$installed_by_script" = "true" ]; then
        echo "Removing composer plugin 'digitalpolygon/drupal-upgrade-plugin'..."
        if ! ddev exec -- composer remove digitalpolygon/drupal-upgrade-plugin --no-interaction; then
            echo "Error removing plugin. Exiting."
            exit 1
        fi
    else
        echo "Composer plugin 'digitalpolygon/drupal-upgrade-plugin' was not installed by this script, leaving it installed."
    fi
}

# Function to upgrade Drupal core to a specific version.
#
# Arguments:
#   $1 - The new Drupal version to update to.
#
# Usage: upgrade_drupal_to_version "10.3.1";
upgrade_drupal_to_version() {
    # Get the target version to update to.
    local new_version=$1
    # Install the 'digitalpolygon/drupal-upgrade-plugin' composer plugin if needed.
    local plugin_installed_by_script=$(install_plugin_if_needed)
    # Perform Drupal core upgrade using the 'digitalpolygon/drupal-upgrade-plugin' composer plugin.
    echo "Upgrading Drupal core to version $new_version..."
    if ! ddev exec -- composer drupal:core:version-change --version="$new_version" --yes; then
        echo "Error upgrading Drupal core to version $new_version. Exiting."
        exit 1
    fi
    # Remove the 'digitalpolygon/drupal-upgrade-plugin' if it was installed by the script.
    remove_plugin_if_installed "$plugin_installed_by_script"
}

# Function to upgrade Drupal core to the latest stable minor version.
#
# Usage: upgrade_drupal_to_latest_minor;
upgrade_drupal_to_latest_minor() {
    # Install the 'digitalpolygon/drupal-upgrade-plugin' composer plugin if needed.
    local plugin_installed_by_script=$(install_plugin_if_needed)
    # Perform Drupal core upgrade using the 'digitalpolygon/drupal-upgrade-plugin' composer plugin.
    echo "Upgrading Drupal core to the latest stable minor version..."
    if ! ddev exec -- composer drupal:core:version-change --latest-minor --yes; then
        echo "Error upgrading Drupal core to the latest stable minor version. Exiting."
        exit 1
    fi
    # Remove the 'digitalpolygon/drupal-upgrade-plugin' if it was installed by the script.
    remove_plugin_if_installed "$plugin_installed_by_script"
}

# Function to upgrade Drupal core to the latest stable major version.
#
# Usage: upgrade_drupal_to_latest_major;
upgrade_drupal_to_latest_major() {
    # Install the 'digitalpolygon/drupal-upgrade-plugin' composer plugin if needed.
    local plugin_installed_by_script=$(install_plugin_if_needed)
    # Perform Drupal core upgrade using the 'digitalpolygon/drupal-upgrade-plugin' composer plugin.
    echo "Upgrading Drupal core to the latest stable major version..."
    if ! ddev exec -- composer drupal:core:version-change --latest-major --yes; then
        echo "Error upgrading Drupal core to the latest stable major version. Exiting."
        exit 1
    fi
    # Remove the 'digitalpolygon/drupal-upgrade-plugin' if it was installed by the script.
    remove_plugin_if_installed "$plugin_installed_by_script"
}

# Function to upgrade Drupal core to the latest stable of the next major version.
#
# Usage: upgrade_drupal_to_next_major;
upgrade_drupal_to_next_major() {
    # Install the 'digitalpolygon/drupal-upgrade-plugin' composer plugin if needed.
    local plugin_installed_by_script=$(install_plugin_if_needed)
    # Perform Drupal core upgrade using the 'digitalpolygon/drupal-upgrade-plugin' composer plugin.
    echo "Upgrading Drupal core to the latest stable of the next major version..."
    if ! ddev exec -- composer drupal:core:version-change --next-major --yes; then
        echo "Error upgrading Drupal core to the latest stable of the next major version. Exiting."
        exit 1
    fi
    # Remove the 'digitalpolygon/drupal-upgrade-plugin' if it was installed by the script.
    remove_plugin_if_installed "$plugin_installed_by_script"
}

# Function to apply database updates using Drush.
#
# Usage: apply_drupal_database_updates;
apply_drupal_database_updates() {
    echo "Applying database updates..."
    if ! ddev exec -- drush updb -y; then
        echo "Error applying database updates. Exiting."
        exit 1
    fi
}

# Function to clear the Drupal cache using Drush.
#
# Usage: clear_drupal_cache;
clear_drupal_cache() {
    echo "Clearing cache..."
    if ! ddev exec -- drush cr; then
        echo "Error clearing Drupal cache. Exiting."
        exit 1
    fi
}

# Function to export the Drupal configuration using Drush.
#
# Usage: export_drupal_configuration;
export_drupal_configuration() {
    echo "Exporting configuration..."
    if ! ddev exec -- drush cex -y; then
        echo "Error exporting Drupal configuration. Exiting."
        exit 1
    fi
}

# Default values.
PROJECT_PATH=$(pwd)
DDEV_PROJECT=$(basename "$PROJECT_PATH") # Automatically generated from the current directory name.
NEW_VERSION=""
LATEST_MINOR=false
LATEST_MAJOR=false
NEXT_MAJOR=false

# Parse the flags passed to the command line arguments.
while (( "$#" )); do
    case "$1" in
        -p)
            PROJECT_PATH=$2
            DDEV_PROJECT=$(basename "$PROJECT_PATH")
            shift 2
            ;;
        -v)
            NEW_VERSION=$2
            shift 2
            ;;
        --latest-minor)
            LATEST_MINOR=true
            shift
            ;;
        --latest-major)
            LATEST_MAJOR=true
            shift
            ;;
        --next-major)
            NEXT_MAJOR=true
            shift
            ;;
        *)
            show_usage
            ;;
    esac
done

# Ensure the project path exists
if [ ! -d "$PROJECT_PATH" ]; then
    echo "Error: Project path '$PROJECT_PATH' does not exist."
    exit 1
fi

# Change to project directory.
cd "$PROJECT_PATH"
# Ensure DDEV is installed
ensure_ddev_installed
# Upgrade Drupal core based on flags
if [ -n "$NEW_VERSION" ]; then
    upgrade_drupal_to_version "$NEW_VERSION"
elif [ "$LATEST_MINOR" = true ]; then
    upgrade_drupal_to_latest_minor
elif [ "$LATEST_MAJOR" = true ]; then
    upgrade_drupal_to_latest_major
elif [ "$NEXT_MAJOR" = true ]; then
    upgrade_drupal_to_next_major
else
    show_usage
fi
# Apply database updates
apply_drupal_database_updates
# Clear cache
clear_drupal_cache
# Export configuration
export_drupal_configuration

# Show confirmation message
echo "Drupal major version upgrade completed successfully."

# Documentation
: <<'END_DOC'
Purpose:
  This script automates the process of performing a major version upgrade for a Drupal site. It ensures that DDEV is installed,
  updates the Drupal core and contributed modules using the composer plugin "digitalpolygon/drupal-upgrade-plugin" if it is
  not already installed, applies any necessary database updates, and exports the configuration files. After the update operation,
  the plugin is removed if it was installed by the script.

Usage Example:
  ./drupal-update.sh -p /path/to/drupal/project -v 10.3.1
  ./drupal-update.sh -p /path/to/drupal/project --latest-minor
  ./drupal-update.sh -p /path/to/drupal/project --latest-major
  ./drupal-update.sh -p /path/to/drupal/project --next-major

Options:
  -p <project_path>  : The path to the Drupal project. Default is the current directory.
  -d <ddev_project>  : The DDEV project name. Default is the current directory name.
  -v <new_version>   : The new Drupal version to update to.
  --latest-minor     : Update to the latest stable minor version within the current major version.
  --latest-major     : Update to the latest stable major version.
  --next-major       : Update to the latest stable of the next major version.

Steps:
  1. Ensures DDEV is installed.
  2. Creates a backup of the current database and files.
  3. Checks if the digitalpolygon/drupal-upgrade-plugin is installed, installs it if not.
  4. Updates Drupal core using the digitalpolygon/drupal-upgrade-plugin.
  5. Removes the digitalpolygon/drupal-upgrade-plugin if it was installed by the script.
  6. Applies any pending database updates.
  7. Clears the cache.
  8. Exports the configuration files.
  9. Outputs a message indicating the completion of the upgrade process and the location of the backups.
END_DOC
