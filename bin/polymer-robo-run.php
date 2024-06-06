<?php

/**
 * @file
 * Execute Polymer commands via Robo.
 */

use DigitalPolygon\Polymer\Polymer;
use Robo\Common\TimeKeeper;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Robo\Config\Config;

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

$config = new Config();
// Execute command.
// phpcs:ignore
$polymer = new Polymer($config, $input, $output);
$status_code = (int) $polymer->run($input, $output);

if (!$input->getFirstArgument() || $input->getFirstArgument() == 'list') {
    $output->writeln("<comment>To create custom BLT commands, see https://docs.acquia.com/blt/extending-blt/#adding-a-custom-robo-hook-or-command.</comment>");
    $output->writeln("<comment>To add BLT commands via community plugins, see https://support.acquia.com/hc/en-us/articles/360046918614-Acquia-BLT-Plugins</comment>");
}

// Stop timer.
$timer->stop();
if ($output->isVerbose()) {
    $output->writeln("<comment>" . $timer->formatDuration($timer->elapsed()) . "</comment> total time elapsed.");
}

exit($status_code);
