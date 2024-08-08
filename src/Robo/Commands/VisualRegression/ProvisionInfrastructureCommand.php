<?php

namespace DigitalPolygon\Polymer\Robo\Commands\VisualRegression;

use Consolidation\AnnotatedCommand\Attributes\Argument;
use Consolidation\AnnotatedCommand\Attributes\Command;
use Consolidation\AnnotatedCommand\Attributes\Option;
use DigitalPolygon\Polymer\Robo\Tasks\TaskBase;
use Robo\Symfony\ConsoleIO;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ProvisionInfrastructureCommand extends TaskBase
{
    protected $stackInfo = null;

    #[Command(name: 'vrt:provision:deploy-static-infrastructure')]
    public function updateStaticInfrastructure(ConsoleIO $io)
    {
        $io->output()->writeln('Ensuring credentials are configured...');
        if ($this->verifyCredentials()) {
            $io->output()->writeln('Credentials confirmed, updating static infrastructure...');
            $provisionTask = $this->taskExecStack();
            $stackId = $this->getConfigValue('vrt.aws.shared-stack-id');
            $region = $this->getConfigValue('vrt.aws.region');
            $accountId = $this->getConfigValue('vrt.aws.account-id');
            $repository = $this->getConfigValue('git.github-repository');
            $stackFile = $this->getConfigValue('vrt.aws.shared-stack-formation-file');
            $provisionTask->exec(sprintf(
                'aws cloudformation deploy --stack-name \'%s\' --template-file \'%s\' --region \'%s\' --capabilities CAPABILITY_NAMED_IAM --parameter-overrides AccountId=%s Repository=%s',
                $stackId,
                $stackFile,
                $region,
                $accountId,
                $repository
            ));
            $provisionTask->printOutput(false);
            $provisionTask->run();
        } else {
            $io->output()->writeln('AWS credentials are not configured. Please run `polymer aws:configure:credentials --help` to see how to configure them.');
        }
    }

    #[Command(name: 'vrt:provision:destroy-static-infrastructure')]
    public function destroySharedInfrastructure(ConsoleIO $io)
    {
        $io->output()->writeln('Ensuring credentials are configured...');
        if ($this->verifyCredentials()) {
            $stackId = $this->getConfigValue('vrt.aws.shared-stack-id');
            $region = $this->getConfigValue('vrt.aws.region');
            $task = $this->taskExecStack();
            $task
                ->exec(sprintf(
                    'aws cloudformation delete-stack --stack-name \'%s\' --region \'%s\'',
                    $stackId,
                    $region
                ))
                ->exec(sprintf(
                    'aws cloudformation wait stack-delete-complete --stack-name \'%s\' --region \'%s\'',
                    $stackId,
                    $region
                ));
            $task->printOutput(false);
            $task->run();
        }
    }

    #[Command(name: 'vrt:backstop:init')]
    public function initVrtFiles()
    {
        $polymerRoot = $this->getConfigValue('polymer.root');
        $repoRoot = $this->getConfigValue('repo.root');
        $githubWorkflowSource = $this->getConfigValue('vrt.ci.github-workflow-source');
        $githubWorkflowDestination = $this->getConfigValue('vrt.ci.github-workflow-destination');
        $this->taskFilesystemStack()
            ->copy($polymerRoot . '/vrt/ddev/docker-compose.backstop.yaml', $repoRoot . '/.ddev/docker-compose.backstop.yaml')
            ->copy($polymerRoot . '/vrt/ddev/docker-compose.polymer.yaml', $repoRoot . '/.ddev/docker-compose.polymer.yaml')
            ->copy($polymerRoot . '/vrt/ddev/commands/backstop', $repoRoot . '/.ddev/commands/backstop/backstop')
            ->copy($polymerRoot . '/vrt/ddev/web-build/Dockerfile.aws', $repoRoot . '/.ddev/web-build/Dockerfile.aws')
            ->copy($polymerRoot . '/vrt/ddev/web-build/Dockerfile.github_cli', $repoRoot . '/.ddev/web-build/Dockerfile.github_cli')
            ->mkdir($repoRoot . '/.github/workflows')
            ->copy($githubWorkflowSource, $githubWorkflowDestination)
            ->mirror($polymerRoot . '/vrt/backstop', $repoRoot . '/tests/backstop')
            ->run();
    }

    #[Command(name: 'vrt:backstop:get-reference-images')]
    public function getReferenceImages()
    {
        $workflowName = 'Visual Regression Reference Images';
        $downloadLocation = '/tmp/backstop-reference-images';
        $backstopDir = $this->getConfigValue('repo.root') . '/tests/backstop';
        $referenceImagesDir = $backstopDir . '/backstop_data/bitmaps_reference';
        $task = $this->taskExec(sprintf(
            'gh run list --workflow \'%s\' --status success --json databaseId',
            $workflowName
        ));
        $task->printOutput(false);
        $task->printMetadata(false);
        $result = $task->run();
        $output = json_decode($result->getMessage(), true);
        $runId = $output[0]['databaseId'];
        $this->taskFilesystemStack()
            ->remove($downloadLocation)
            ->run();
        $task = $this->taskExec(sprintf(
            'gh run download \'%s\' -n backstop-reference-images --dir \'%s\'',
            $runId,
            $downloadLocation
        ));
        $task->run();
        $this->taskFilesystemStack()
            ->remove($referenceImagesDir)
            ->mirror('/tmp/backstop-reference-images', $referenceImagesDir)
            ->run();
    }

    #[Command(name: 'vrt:backstop:put-test-report')]
    #[Argument(name: 'stack_id', description: 'The stack ID of the shared infrastructure.')]
    public function putTestReport(string $stack_id)
    {
        $backstopDataDir = $this->getConfigValue('repo.root') . '/tests/backstop/backstop_data';
        $bucketId = 'polymer-backstopjs-html-report-' . $stack_id;
        $task = $this->taskExec(sprintf(
            'aws s3 sync \'%s\' \'s3://%s\' --delete',
            $backstopDataDir,
            $bucketId
        ));
        $task->run();
    }

    #[Command(name: 'vrt:provision:set-basic-auth-credentials')]
    public function setBasicAuthCredentials(string $username, string $password)
    {
        $info = $this->getKeyValueStoreInfo();
        $task = $this->taskExec(sprintf(
            'aws cloudfront-keyvaluestore put-key --kvs-arn \'%s\' --key \'%s\' --value \'%s\' --if-match \'%s\'',
            $info['keyValueStoreArn'],
            'report-username',
            $username,
            $info['etag']
        ));
        $task->printOutput(false);
        $task->printMetadata(false);
        $task->run();

        // Call the key value store info again to get the new etag.
        $info = $this->getKeyValueStoreInfo();
        $task = $this->taskExec(sprintf(
            'aws cloudfront-keyvaluestore put-key --kvs-arn \'%s\' --key \'%s\' --value \'%s\' --if-match \'%s\'',
            $info['keyValueStoreArn'],
            'report-password',
            $password,
            $info['etag']
        ));
        $task->printOutput(false);
        $task->printMetadata(false);
        $task->run();
    }

    protected function getKeyValueStoreInfo()
    {
        $stackId = $this->getConfigValue('vrt.aws.shared-stack-id');
        if ($this->stackInfo) {
            $stackInfo = $this->stackInfo;
        } else {
            $task = $this->taskExec(sprintf(
                'aws cloudformation describe-stacks --stack-name %s',
                $stackId
            ));
            $task->printOutput(false);
            $task->printMetadata(false);
            $result = $task->run();
            $exit_code = $result->getExitCode();
            if ($exit_code === 0) {
                $output = json_decode($result->getMessage(), true);
                $this->stackInfo = $output;
                $stackInfo = $this->stackInfo;
            }
        }

        if ($stackInfo) {
            foreach ($stackInfo['Stacks'] as $stackDelta => $stack) {
                if ($stackId === $stack['StackName']) {
                    $outputs = $stack['Outputs'];
                    foreach ($outputs as $outputDelta => $output) {
                        if ($output['OutputKey'] === 'BackstopReportBasicAuthKeyValueStoreArn') {
                            $keyValueStoreArn = $output['OutputValue'];
                        }
                    }
                }
            }

            if ($keyValueStoreArn) {
                $task = $this->taskExec(sprintf(
                    'aws cloudfront-keyvaluestore describe-key-value-store --kvs-arn \'%s\'',
                    $keyValueStoreArn
                ));
                $task->printOutput(false);
                $task->printMetadata(false);
                $result = $task->run();
                $exit_code = $result->getExitCode();
                if ($exit_code === 0) {
                    $output = json_decode($result->getMessage(), true);
                    $etag = $output['ETag'];
                }
                return [
                    'keyValueStoreArn' => $keyValueStoreArn,
                    'etag' => $etag
                ];
            }
        }

        return false;
    }

    #[Command(name: 'aws:configure:credentials')]
    #[Argument(name: 'access_key', description: 'The AWS access key.')]
    #[Argument(name: 'secret_key', description: 'The AWS secret key.')]
    public function configureAwsCredentials(string $access_key, string $secret_key)
    {
        $task = $this->taskExecStack();
        $region = $this->getConfigValue('vrt.aws.region');
        $task->exec('aws configure set aws_access_key_id ' . $access_key);
        $task->exec('aws configure set aws_secret_access_key ' . $secret_key);
        $task->exec('aws configure set region ' . $region);
        $task->printMetadata(false);
        $task->stopOnFail();
        $result = $task->run();
    }

    protected function verifyCredentials()
    {
        $task = $this->taskExecStack();
        $task->exec('aws sts get-caller-identity');
        $task->printMetadata(false);
        $task->printOutput(false);
        $result = $task->run();
        $exit_code = $result->getExitCode();
        if ($exit_code !== 0) {
            return false;
        }
        return true;
    }
}
