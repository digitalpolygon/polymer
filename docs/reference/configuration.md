# Configuration reference (core)

Defaults shipped by `digitalpolygon/polymer` in its `config/default.yml`.
Override any key in your project's `polymer/polymer.yml` (scaffolded by
`polymer:init`), or at runtime with `-D key=value`. See
[Configuration management](../developing/configuration.md) for how contexts
layer and how `${token}` interpolation works.

## Top-level

| Key | Default | Description |
| --- | --- | --- |
| `hide-internal-commands` | `true` | Hide every command prefixed `internal:` from `list` output. This is applied before annotation processing, so `#[Help(hidden: false)]` cannot reverse it. |

## `project`

| Key | Default | Description |
| --- | --- | --- |
| `project.human_name` | `My Polymer site` | Human-readable project name. |
| `project.machine_name` | `my_polymer_site` | Machine name; used in derived values like the local hostname. |
| `project.local.hostname` | `local.${project.machine_name}.com` | Local development hostname. |
| `project.local.protocol` | `http` | Local development protocol. |
| `project.local.uri` | `${project.local.protocol}://${project.local.hostname}` | Full local URI, derived from the two keys above. |
| `project.type` | `php` | Project type. |
| `project.recipe` | `common` | Build recipe identifier. |

## `deploy`

Controls `artifact:*` builds and deployments.

| Key | Default | Description |
| --- | --- | --- |
| `deploy.build-dependencies` | `true` | Whether the artifact build installs production dependencies. |
| `deploy.dir` | `${tmp.dir}/polymer-deploy` | Working directory for assembling the deploy artifact. |
| `deploy.docroot` | `${deploy.dir}/docroot` | Docroot inside the artifact. |
| `deploy.exclude_file` | `${polymer.root}/config/deploy-exclude.txt` | Base list of paths excluded from the artifact. |
| `deploy.exclude_additions_file` | `${repo.root}/polymer/deploy-exclude-additions.txt` | Project-level additions to the exclude list. |
| `deploy.gitignore_file` | `${polymer.root}/config/.gitignore` | `.gitignore` written into the artifact. |

## `git`

| Key | Default | Description |
| --- | --- | --- |
| `git.default_branch` | `main` | The project's default branch. |
| `git.default-branch` | `${git.default_branch}` | Alias of the above (kept for compatibility). |
| `git.user.name` | `Polymer Bot` | Commit author name used for Polymer-generated commits (e.g. artifact deploys). |
| `git.user.email` | `nobody@example.com` | Commit author email for Polymer-generated commits. |
| `git.remotes` | *(unset)* | List of git remotes that `artifact:deploy` pushes to. Host extensions may inspect it (e.g. polymer-pantheon-drupal validates branch names when a Pantheon remote is present). |

## Runtime-derived keys

These are computed during boot/command execution rather than defaulted in
YAML, and are available for interpolation in your own config:

| Key | Description |
| --- | --- |
| `repo.root` | The project root — the directory containing `.polymer/`. |
| `polymer.root` | Install path of the `digitalpolygon/polymer` package. |
| `composer.bin` | The project's Composer `vendor/bin` directory. |
| `tmp.dir` | System temp directory. |
| `extension.<id>.root` | Install path of each enabled extension. |
