<?php

namespace DigitalPolygon\PolymerTest\phpunit\kernel;

/**
 * Scaffolds a brand-new project-local extension into the fixture and drives
 * it through the production boot path — the full authoring surface from the
 * extension guide: marker file, ExtensionInfo, runtime namespace
 * registration, command discovery, default config + project override, and
 * the CommandInvoker.
 */
class CustomExtensionTest extends PolymerKernelTestCase
{
    /**
     * Each scaffold gets a unique extension id (and therefore a unique PSR-4
     * prefix): registerExtensionNamespaces() mutates the process-global class
     * loader, so reusing an id across tests would resolve classes from a
     * previous test's (deleted) fixture.
     */
    private function scaffoldExtension(): string
    {
        $id = 'kfix' . bin2hex(random_bytes(3));
        $root = $this->projectRoot . '/.polymer/plugins/' . $id;
        mkdir($root . '/src/Plugin/Commands', 0777, true);
        mkdir($root . '/config', 0777, true);

        file_put_contents($root . '/' . $id . '.poly_info.yml', "name: Kernel fixture extension\n");
        file_put_contents($root . '/config/default.yml', "fixture:\n  flavor: vanilla\n");

        file_put_contents($root . '/src/ExtensionInfo.php', <<<PHP
<?php

namespace DigitalPolygon\\Polymer\\$id;

use DigitalPolygon\\Polymer\\Core\\Robo\\Extension\\PolymerExtensionBase;

class ExtensionInfo extends PolymerExtensionBase
{
    public static function getExtensionName(): string
    {
        return '$id';
    }
}
PHP);

        file_put_contents($root . '/src/Plugin/Commands/FixtureCommands.php', <<<PHP
<?php

namespace DigitalPolygon\\Polymer\\$id\\Plugin\\Commands;

use Consolidation\\AnnotatedCommand\\Attributes\\Command;
use DigitalPolygon\\Polymer\\Core\\Robo\\Tasks\\TaskBase;
use Robo\\Symfony\\ConsoleIO;

class FixtureCommands extends TaskBase
{
    #[Command(name: 'fixture:flavor')]
    public function flavor(ConsoleIO \$io): void
    {
        \$io->writeln('flavor=' . \$this->getConfigValue('fixture.flavor'));
    }

    #[Command(name: 'fixture:outer')]
    public function outer(ConsoleIO \$io): void
    {
        \$this->commandInvoker->invokeCommand(\$io->input(), 'fixture:flavor');
    }
}
PHP);

        return $id;
    }

    public function testScaffoldedExtensionIsDiscoveredAndItsCommandRuns(): void
    {
        $id = $this->scaffoldExtension();
        $this->enableExtensions([$id]);

        $polymer = $this->bootPolymer();
        $commands = $this->runOk($polymer, 'list --raw');
        $this->assertStringContainsString('fixture:flavor', $commands);

        // The extension's default.yml supplies the value: the extension
        // context loaded, the runtime namespace resolved, the command ran.
        $output = $this->runOk($polymer, 'fixture:flavor');
        $this->assertStringContainsString('flavor=vanilla', $output);
    }

    public function testProjectConfigOverridesExtensionDefault(): void
    {
        $id = $this->scaffoldExtension();
        $this->enableExtensions([$id]);
        $this->writePolymerYml(['fixture' => ['flavor' => 'chocolate']]);

        $polymer = $this->bootPolymer();
        $output = $this->runOk($polymer, 'fixture:flavor');

        // project context sits above the extension context in the stack.
        $this->assertStringContainsString('flavor=chocolate', $output);
    }

    public function testCommandInvokerRunsSubCommandsInProcess(): void
    {
        $id = $this->scaffoldExtension();
        $this->enableExtensions([$id]);

        $polymer = $this->bootPolymer();
        $output = $this->runOk($polymer, 'fixture:outer');

        $this->assertStringContainsString('flavor=vanilla', $output);
    }

    public function testDisabledScaffoldedExtensionIsNotAutoloadable(): void
    {
        $id = $this->scaffoldExtension();
        // Never enabled: discovery sees it on disk, but its namespace must
        // not be registered — autoloader gating is the point of disabling.
        $this->bootPolymer();

        $this->assertFalse(
            class_exists("DigitalPolygon\\Polymer\\$id\\ExtensionInfo"),
            'A disabled extension must not have its namespace registered with the autoloader.'
        );
    }
}
