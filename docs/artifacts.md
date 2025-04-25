# Artifact and Build Management

An artifact is a byproduct of software development that represents the code, libraries, or packages used in building and
deploying applications. With Polymer, you can configure artifacts and its dependent builds that can then be compiled and
deployed to a Git repository.

## Builds

Builds are definitions of operations needed to generate a specific asset. A given build may specify a setup procedure
and an asset generation procedure. The setup procedure should be used to install dependencies needed to build the assets. The
assets procedure should then be used to execute the compilation of the asset.

Example build, identified as `my-theme`:

```yaml
builds:
  my-theme:
    dir: '${repo.root}'
    setup: npm ci
    assets: npm run build
```

To execute the complete build:

```bash
polymer build my-theme
```

To just run the setup procedure:

```bash
polymer build:reqs my-theme
```

To just run the assets generation procedure:

```bash
polymer build:assets my-theme
```

## Artifacts

### Configuring

Artifacts are defined in the `artifacts` section of the configuration file. Declared artifacts may specify builds they
are dependent on.

Below is an example of a configured artifact, identified by `artifact-id`:

```yaml
artifacts:
  artifact-id:
    dependent-builds:
      - my-theme
```

To generate that artifact, run:

```bash
polymer artifact:compile artifact-id
```

The above will:

1. Execute all dependent builds.
2. Rsync the codebase to the artifact assembly workspace.
3. Install Composer without dev dependencies.

### Deploying

Artifacts are compiled and then deployed via Git. You may deploy the artifact as a branch or as a tag.

Example deployment command:

```bash
polymer artifact:deploy artifact-id --branch destination-branch
```

The above will result in the artifact being compiled and then deployed via the named branch to **_all configured_** Git
remotes.

You can further configure the deployment by overriding the default deployment configuration.

#### Deployment configuration

```yaml
deploy:
  build-dependencies: true
  dir: ${tmp.dir}/polymer-deploy
  docroot: ${deploy.dir}/docroot
  exclude_file: ${polymer.root}/config/deploy-exclude.txt
  exclude_additions_file: ${repo.root}/polymer/deploy-exclude-additions.txt
  gitignore_file: ${polymer.root}/config/.gitignore
```

| Key                             | Description                                                                                                                        |
|---------------------------------|------------------------------------------------------------------------------------------------------------------------------------|
| `deploy.build-dependencies`     | If set to `true`, dependent builds will be executed before the artifact is deployed.                                               |
| `deploy.dir`                    | The directory where the artifact is built.                                                                                         |
| `deploy.docroot`                | The location of the docroot directory in the artifact.                                                                             |
| `deploy.exclude_file`           | The file that contains the list of files and directories to exclude from the deployment. Used by rsync.                            |
| `deploy.exclude_additions_file` | The file that contains an additional list of files and directories to exclude from the deployment. Used by rsync.                  |
| `deploy.gitignore_file`         | The file that contains the list of files and directories to exclude from the deployment. Used by git when committing the artifact. |

#### Git remote configuration

Git remotes can be configured as key value pairs in the `git.remotes` section of the configuration file. The key is
remote ID and the value is the remote URL. For example:

```yaml
git:
  remotes:
    origin: git@github.com:repo-org/repo.git
```
