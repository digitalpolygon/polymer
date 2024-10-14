# Configuration Discovery

Polymer and its extensions can each provide their own configuration file.

ConfigOverlay contexts are used to layer in configuration in priority order.

- default
- extension1 default
- extension2 default
- project
- project.environment
- site
- site.environment
- process

Classes used to initialize configuration:

- YamlConfigLoader
- ConfigProcessor
- Config
- ConfigOverlay

Every extension can provide its own configuration file.
