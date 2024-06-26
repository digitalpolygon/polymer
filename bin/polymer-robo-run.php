<?php

/**
 * @file
 * Execute Polymer commands via Robo.
 */

use DigitalPolygon\Polymer\Robo\Config\ConfigInitializer;
use DigitalPolygon\Polymer\Robo\Polymer;
use Robo\Common\TimeKeeper;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

// Start Timer.
$timer = new TimeKeeper();
$timer->start();

// Initialize input and output.
$input = new ArgvInput($argv);
$output = new ConsoleOutput();

// Write BLT version for debugging.
if ($output->isVerbose()) {
    $output->writeln("<comment>Polymer version " . Polymer::getVersion() . "</comment>");
}

// Initialize configuration.
/** @var string $repo_root */
$repo_root = find_repo_root();
$config_initializer = new ConfigInitializer($repo_root, $input);
$config = $config_initializer->initialize();

// Execute command.
// @phpstan-ignore variable.undefined
$polymer = new Polymer($config, $input, $output, $classLoader);
$status_code = (int) $polymer->run($input, $output);

// Stop timer.
$timer->stop();
$elapsed = $timer->elapsed();
if ($output->isVerbose() && $elapsed != null) {
    $output->writeln("<comment>" . $timer->formatDuration($elapsed) . "</comment> total time elapsed.");
}

exit($status_code);
