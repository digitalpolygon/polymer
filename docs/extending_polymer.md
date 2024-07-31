# Extending and Overriding Polymer WebOps Tooling

Polymer provides a comprehensive plugin and configuration system to support extensive customization.

## Adding a Custom Robo Hook or Command

Polymer utilizes [Robo](https://github.com/consolidation/Robo) and the [Annotated Command](https://github.com/consolidation/annotated-command) library for defining commands. With these tools, you can create new custom commands or hook into existing Polymer commands, allowing you to execute custom code before or after predefined commands.

You can place custom commands in a directory within your project or in a separate Composer package, as long as the command files are exposed using PSR-4 autoloading.

### Steps to Create a Custom Robo PHP Command or Hook:

1. **Create the Command File**:
    - Name the file using the `*Commands.php` pattern. For example, `ExampleCommands.php`.

2. **Define Namespace and Autoloading**:
    - Use a namespace that ends in `\Polymer\Plugin\Commands`, and ensure it's exposed using PSR-4 in your `composer.json` file. For example, `Example\Polymer\Plugin\Commands`.

3. **Write Your Command**:
    - Follow the [Robo PHP Getting Started Guide](https://robo.li/getting-started.html) to create your custom command.
    - Refer to [Annotated Command’s Hook Types](https://github.com/consolidation/annotated-command#hooks) for a list of available hook types.

### Example:

For a practical example of custom commands implementation, see the [Drupal Integration for Polymer WebOps Tooling](https://github.com/digitalpolygon/polymer-drupal).

## Replacing or Overriding a Robo Command

To replace an existing Polymer command with your custom version, use the [replace command annotation](https://github.com/consolidation/annotated-command#replace-command-hook) in your custom command.

---

This guide provides the essential steps to extend and customize Polymer WebOps Tooling according to your project’s requirements.
