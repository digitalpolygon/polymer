<?php

namespace DigitalPolygon\PolymerTests\phpunit\unit;

use PHPUnit\Framework\TestCase;
use Consolidation\Config\Config;
use DigitalPolygon\Polymer\Robo\Config\PolymerConfig;

class ConfigurationContextsTest extends TestCase
{
    /**
     * Adds an extra context to the configuration.
     *
     * @param PolymerConfig $config
     * @return void
     */
    protected function addExtraContext(PolymerConfig $config): void
    {
        $config->addContext('extra', new Config([
            'extra-key' => 'extra-value',
        ]));
    }
    public function testConfigurationPlaceholdersResolve(): void
    {
        // Create the configuration contexts from YAML
        $polymerContext = new Config([
            'polymer' => [
                'my-value-1' => 'value',
                'my-value-2' => '${polymer.my-value-1}',
            ],
        ]);

        $config = new PolymerConfig();
        $config->addContext('polymer', $polymerContext);
        $this->addExtraContext($config);

        $this->assertEquals($config->get('polymer.my-value-2'), 'value');
    }

    public function testHigherPriorityContextValueUsedByLowLevelReference(): void
    {
        // Create the configuration contexts from YAML
        $polymerContext = new Config([
            'polymer' => [
                'my-value-1' => null,
                'my-value-2' => '${polymer.my-value-1}',
            ],
        ]);

        $projectContext = new Config([
            'polymer' => [
                'my-value-1' => 'value-1',
            ],
        ]);

        // Create the primary configuration object
        $config = new PolymerConfig();

        // Add the contexts in the specified order
        $config->addContext('polymer', $polymerContext);
        $config->addContext('project', $projectContext);
        $this->addExtraContext($config);

        $this->assertEquals($config->get('polymer.my-value-1'), 'value-1');
        $this->assertEquals($config->get('polymer.my-value-2'), 'value-1');
    }

    public function testHigherPriorityContextValuesReferenceHigherLevel(): void
    {
        // Create the configuration contexts from YAML
        $polymerContext = new Config([
            'polymer' => [
                'my-value-1' => null,
                'my-value-2' => null,
            ],
        ]);

        $projectContext = new Config([
            'polymer' => [
                'my-value-1' => 'value-1',
                'my-value-2' => '${polymer.my-value-1}',
            ],
        ]);

        // Create the primary configuration object
        $config = new PolymerConfig();

        // Add the contexts in the specified order
        $config->addContext('polymer', $polymerContext);
        $config->addContext('project', $projectContext);
        $this->addExtraContext($config);

        $this->assertEquals($config->get('polymer.my-value-1'), 'value-1');
        $this->assertEquals($config->get('polymer.my-value-2'), 'value-1');
    }

    public function testHigherPriorityContextValueSetsLowerPriorityConfigurationReference(): void
    {
        // Create the configuration contexts from YAML
        $polymerContext = new Config([
            'polymer' => [
                'my-value-2' => '${polymer.my-value-1}',
            ],
        ]);
        $projectContext = new Config([
            'polymer' => [
                'my-value-1' => 'value-1',
            ],
        ]);

        // Create the primary configuration object
        $config = new PolymerConfig();

        // Add the contexts in the specified order
        $config->addContext('polymer', $polymerContext);
        $config->addContext('project', $projectContext);
        $this->addExtraContext($config);

        $this->assertEquals($config->get('polymer.my-value-1'), 'value-1');
        $this->assertEquals($config->get('polymer.my-value-2'), 'value-1');
    }

    public function testCyclicContextValueReferences(): void
    {
        // Create the configuration contexts from YAML
        $polymerContext = new Config([
            'polymer' => [
                'my-value-2' => '${polymer.my-value-1}',
            ],
        ]);
        $projectContext = new Config([
            'polymer' => [
                'my-value-1' => '${polymer.my-value-2}',
            ],
        ]);

        // Create the primary configuration object
        $config = new PolymerConfig();

        // Add the contexts in the specified order
        $config->addContext('polymer', $polymerContext);
        $config->addContext('project', $projectContext);
        $this->addExtraContext($config);

        $this->assertEquals($config->get('polymer.my-value-1'), '${polymer.my-value-2}');
        $this->assertEquals($config->get('polymer.my-value-2'), '${polymer.my-value-2}');
    }

    public function testSetProcessContextWithReference(): void
    {
        $polymerContext = new Config([
            'polymer' => [
                'my-value-2' => '${polymer.my-value-1}',
            ],
        ]);
        $projectContext = new Config([
            'polymer' => [
                'my-value-1' => 'value-1',
            ],
        ]);

        // Create the primary configuration object
        $config = new PolymerConfig();

        // Add the contexts in the specified order
        $config->addContext('polymer', $polymerContext);
        $config->addContext('project', $projectContext);
        $this->addExtraContext($config);

        $config->set('polymer.my-value-3', '${polymer.my-value-2}');

        $this->assertEquals($config->get('polymer.my-value-3'), 'value-1');
    }

    public function testSetProcessContextWithKeySetsLowerContextWhichReferences(): void
    {
        $polymerContext = new Config([
            'polymer' => [
                'my-value-1' => '${process.key}',
            ],
        ]);

        // Create the primary configuration object
        $config = new PolymerConfig();

        // Add the contexts in the specified order
        $config->addContext('polymer', $polymerContext);
        $this->addExtraContext($config);

        $this->assertEquals($config->get('polymer.my-value-1'), '${process.key}');

        $config->set('process.key', 'new-process-value');
        $this->assertEquals($config->get('polymer.my-value-1'), 'new-process-value');
    }
}
