# Development

## DDEV

This application is developed with [DDEV](https://ddev.readthedocs.io/).

### Custom commands

#### `ddev init`

Initializes the development environment for this tool.

#### `ddev build-docs`

Builds the documentation site for this tool.

!!! tip

    Run `ddev launch :444` to open the site in your browser after running `ddev
    build-docs`.

#### `ddev dev-docs`

Starts the mkdocs build server and watches for changes.

Run `docker ps` and find the container ID for the `mkdocs`
container. Find the external port mapped to port 8000 of the container, open a browser and navigate to
`http://127.0.0.1:<external port>`.

!!! tip

    When making changes to the documentation, this is the recommended method to use as it hot reloads changes in the
    browser.

## Building for Drupal

This section documents the supported methods for building Polymer features for Drupal.

### How to add features for Drupal

The codebase that is used to develop for Drupal is https://github.com/digitalpolygon/polymer-drupal-pantheon-testing.
For example, if you wanted to add a new Drupal-related feature to Polymer, you would:

1. Clone the [Pantheon Drupal repository](https://github.com/digitalpolygon/polymer-drupal-pantheon-testing) locally and
   change into its directory.
2. Setup a github Personal Access Tokens.
   1. Go to https://github.com/settings/tokens and and generate a new token.
3. Configure Composer to use your personal access token
   1. `ddev composer config -g github-oauth.github.com XXXXXXXXXXXXXXXXXXXXXXX`
4. Run `ddev init`.
5. Run `ddev exec bash` to get a bash shell in the container.
6. Run `composer require digitalpolygon/polymer:dev-main --prefer-source`. This will add the Polymer codebase as a
   cloned copy based on the latest `main`. Since this is added as a code repository, you can make changes directly out
   of it's vendor package directory.
7. Change directory to `vendor/digitalpolygon/polymer`.
8. Checkout a new branch of work (e.g. `feature/PWT-000-my-new-feature`).
9.  Run `git push` to push the branch to the Polymer repository.

After following the above steps you will be able to make changes to the Polymer codebase and see them reflected in the
Drupal application in real-time.

!!! note

    If you need to make parallel changes to Polymer **_and the Drupal application_** to fully demonstrate the new work,
    you should also create a feature branch for the Drupal application using the same branch name you used for the
    Polymer work.

### Specific hosts

#### Pantheon

The Pantheon site used to develop Drupal featuers is https://dashboard.pantheon.io/sites/a12fdbc5-c40e-4f88-993f-9788f838142a#dev/code.
