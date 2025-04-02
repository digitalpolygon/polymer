<?php

namespace DigitalPolygon\Polymer\Robo\Workflow;

use DigitalPolygon\Polymer\Robo\Config\ConfigAwareTrait;
use League\Container\ContainerAwareTrait;
use Psr\Log\LoggerAwareTrait;
use Robo\Common\IO;
use Robo\Exception\TaskException;
use Robo\LoadAllTasks;

trait WorkflowHelperTrait
{
    use LoggerAwareTrait;
    use ConfigAwareTrait;
    use ContainerAwareTrait;
    use LoadAllTasks; // uses TaskAccessor, which uses BuilderAwareTrait
    use IO;

    /**
     * @param string $configKey
     * @param string $extension
     * @param string $platform
     * @param string[] $configuredWorkflow
     * @return int
     */
    public function generateWorkflowFilesFromExtensionAndConfigKey(string $extension, string $configKey, string $platform): int
    {
        $platformConfigKey = "$configKey.$platform";
        $configuredWorkflows = $this->getConfigValue($platformConfigKey, []);
        $extensionRoot = $this->getConfigValue("extension.$extension.root");
        $workflowsDir = "$extensionRoot/workflows/$platform";
        return $this->generateWorkflowFiles($workflowsDir, $platform, $configuredWorkflows);
    }

    /**
     * @param string $workflowSourceDir
     * @param string $platform
     * @param string[] $configuredWorkflows
     * @return int
     */
    public function generateWorkflowFiles(string $workflowSourceDir, string $platform, array $configuredWorkflows): int
    {
        $this->say("Generating workflows for platform: <comment>$platform</comment>");
        if (empty($platformDir = $this->getPlatformDirectory($platform))) {
            $this->logger?->error("Error: <error>Invalid platform: $platform</error>");
            return 1;
        }
        $workflowFilePaths = array_map(function ($file) use ($workflowSourceDir) {
            return "$workflowSourceDir/$file";
        }, $configuredWorkflows);
        $workflowFilePaths = array_filter($workflowFilePaths, 'is_file');
        if (is_dir($workflowSourceDir)) {
            $task = $this->taskFilesystemStack()
                ->stopOnFail();
            if (!is_dir($platformDir)) {
                $task->mkdir($platformDir);
            }
            foreach ($workflowFilePaths as $workflowFilePath) {
                $file = basename($workflowFilePath);
                $file = "$platformDir/$file";
                $task->copy($workflowFilePath, $file);
            }
            try {
                $task->run();
            } catch (TaskException $e) {
                $this->logger?->error("Error: <error>{$e->getMessage()}</error>");
                return 1;
            }
        }
        return 0;
    }

    protected function getPlatformDirectory(string $platform): string|false
    {
        $polymerRoot = $this->getConfigValue('repo.root');
        if ('github' === $platform) {
            return "$polymerRoot/.github/workflows";
        }
        return false;
    }
}
