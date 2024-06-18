# Polymer: WebOps Tooling

Polymer is a tool to help enable developers facilitate their responsibilities in the WebOps space. It is a collection of
tools and scripts that help automate the process of deploying and managing web applications.

## Setup

Clone the repository and run `ddev init`.

### DDEV Commands

#### `ddev init`

Initializes the development environment for this tool.

#### `ddev build-docs`

Builds the documentation site for this tool.

!!! tip

    Run `ddev launch :444` to open the site in your browser after running `ddev
    build-docs`.

## Testing

Run `ddev composer validations` to run the tests.

## Project Information

### Issue tracking

This project uses Jira to track issues. See https://digitalpolygon.atlassian.net/jira/software/projects/PWT/boards/96.

!!! note

    In the future this may be changed  to use GitHub issue tracking if the project is open sourced.

### Branch management

#### Features and bug fixes

* New features or bug fix branches should be made off of the `main` branch.
* Any feature or bug being developed _MUST HAVE_ an accompanying issue in Jira. The PR will not be merged without this.
* Feature branches should always have a base branch of `main` in pull requests.

#### Subtasks and feature branches

* If a feature or bug fix issue is divided into subtasks, then a feature branch should be created that references the
feature issue and have a *draft PR* opened into `main`.
* Each subtask should have its own branch based off of the parent feature branch.
* Once all subtasks are complete and merged into the parent feature branch, the feature branch can be converted to a
  normal PR, ready for review.

## Documenting commands

When specifying commands in documentation and other sources, the [docopt](http://docopt.org/) standard should be used.
