# composer:validate:security

```shell
polymer composer:validate:security [--no-dev] [--locked]
```

Validates the security of the current project's dependencies.

If `--no-dev` is specified, only the dependencies in the `require` section of the `composer.json` file will be checked.

If `--locked` is specified, the `composer.lock` file will be used to validate the dependencies.
