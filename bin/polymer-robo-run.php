<?php

/**
 * @file
 * Execute Polymer commands via Robo.
 */

use DigitalPolygon\Polymer\Core\Robo\Polymer;
use Robo\Common\TimeKeeper;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

$cwd = isset($_SERVER['PWD']) && is_dir($_SERVER['PWD']) ? $_SERVER['PWD'] : getcwd();

$autoloadFile = false;
// Set up autoloader
$candidates = [
    $_composer_autoload_path ?? __DIR__ . '/../vendor/autoload.php', // https://getcomposer.org/doc/articles/vendor-binaries.md#finding-the-composer-autoloader-from-a-binary
    __DIR__ . '/vendor/autoload.php', // For development of Polymer itself.
];
foreach ($candidates as $candidate) {
    if (file_exists($candidate)) {
        $autoloadFile = $candidate;
        break;
    }
}
if (!$autoloadFile) {
    throw new \Exception("Could not locate autoload.php. cwd is $cwd; __DIR__ is " . __DIR__);
}
$classLoader = include $autoloadFile;
if (!$classLoader) {
    throw new \Exception("Invalid autoloadfile: $autoloadFile. cwd is $cwd; __DIR__ is " . __DIR__);
}

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
/** @var string|null $repoRoot */
$repoRoot = null;
for ($i = 0; $i < 10; $i++) {
    if (file_exists($cwd . '/.polymer')) {
        $repoRoot = $cwd;
        break;
    }
    $parent = dirname($cwd);
    if ($parent === $cwd) {
        // We have reached the root of the filesystem.
        break;
    }
    $cwd = $parent;
}

if (!$repoRoot) {
    throw new \Exception("Could not find .polymer directory in this or any parent directory. cwd is $cwd; __DIR__ is " . __DIR__);
}

// Execute command.
$polymer = new Polymer($repoRoot, $input, $output, $classLoader);
$polymer->boot();
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
