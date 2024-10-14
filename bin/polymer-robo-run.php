<?php

/**
 * @file
 * Execute Polymer commands via Robo.
 */

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
/** @var string $repoRoot */
$repoRoot = find_repo_root();

// Execute command.
// @phpstan-ignore variable.undefined
$polymer = new Polymer($repoRoot, $input, $output, $classLoader);
$status_code = (int) $polymer->run($input, $output);

// Stop timer.
$timer->stop();
$elapsed = $timer->elapsed();
if ($output->isVerbose() && $elapsed != null) {
    $output->writeln("<comment>" . $timer->formatDuration($elapsed) . "</comment> total time elapsed.");
}

$container = $polymer->getContainer();
/** @var \Symfony\Component\EventDispatcher\Debug\TraceableEventDispatcher $eventDispatcher */
$eventDispatcher = $container->get('eventDispatcher');
//$called = $eventDispatcher->getCalledListeners();
//$notCalled = $eventDispatcher->getNotCalledListeners();

exit($status_code);
