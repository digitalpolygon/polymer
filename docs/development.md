# Development

## DDEV

This application is developed with [DDEV](https://ddev.readthedocs.io/).

### Custom commands

#### `ddev init`

Initializes the development environment for this tool.

#### `ddev build-docs`

Builds the documentation site for this tool.

When updating command documentation, run `polymer mk:docs` to re-generate these files.

!!! tip

    Run `ddev launch :444` to open the site in your browser after running `ddev
    build-docs`.

#### `ddev dev-docs`

Starts the mkdocs build server and watches for changes.

Run `ddev launch :445` to open the dev site in your browser.

When updating command documentation, run `polymer mk:docs` to re-generate these files.

!!! tip

    When making changes to the documentation, this is the recommended method to use as it hot reloads changes in the
    browser.
