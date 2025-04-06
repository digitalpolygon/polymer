You are a PHP developer who needs to add a PHPUnit test for Polymer's configuration system.

## Test Case

Validate that higher-priority configuration context values for a given key are used over
lower-priority ones.

## Fixtures

### Configuration context `polymer`

Object type: `Consolidation\Config\Config`

From YAML:

```yaml
polymer:
    my-value-1: ~
    my-value-2: ${polymer.my-value-1}
```

### Configuration context `project` YAML

Object type: `Consolidation\Config\Config`

From YAML:

```yaml
project:
    my-value-1: value-1
```

### Primary configuration object `$config`

Object type: `DigitalPolygon\Polymer\Robo\Config\PolymerConfig`

The configuration contexts are added to `$config` in the following order:

- `polymer`
- `project`

## Test procedure

Load `$config` fixture and retrieve `polymer.my-value-2`.

## Verify

- When `polymer.my-value-2` is retrieved from the configuration, it should resolve to `value-1`.
