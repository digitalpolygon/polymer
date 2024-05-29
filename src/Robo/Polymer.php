<?php

namespace DigitalPolygon\Polymer\Robo;

use DigitalPolygon\Polymer\Commands\Artifact\BuildCommand;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Robo\Common\ConfigAwareTrait;
use Robo\Config\Config;
use Robo\Robo;
use Robo\Runner as RoboRunner;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Polymer implements ContainerAwareInterface {

    use ContainerAwareTrait;

    const APPLICATION_NAME = 'Polymer';
    const REPOSITORY = 'digitalpolygon/polymer';

    use ConfigAwareTrait;

    private $runner;

    public function __construct(
        Config $config,
        InputInterface $input = NULL,
        OutputInterface $output = NULL
    ) {

        // Create applicaton.
        $this->setConfig($config);
//        $application = new Application(self::APPLICATION_NAME, $config->get('version'));
        $application = new Application(self::APPLICATION_NAME);
        // Create and configure container.
        $container = Robo::createContainer($application, $config);
//        $container->add(MyCustomService::class); // optional
        Robo::finalizeContainer($container);

        // Instantiate Robo Runner.
        $this->runner = new RoboRunner([
            BuildCommand::class,
        ]);
        $this->setContainer($container);
        $this->runner->setContainer($container);
        $this->runner->setSelfUpdateRepository(self::REPOSITORY);
    }

    public function run(InputInterface $input, OutputInterface $output) {
        $application = $this->getContainer()->get('application');
        $status_code = $this->runner->run($input, $output, $application, [
            BuildCommand::class,
        ]);

        return $status_code;
    }

    public static function getVersion() {
        return 'latest';
    }

}
