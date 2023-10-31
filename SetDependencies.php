#!/usr/bin/env php
<?php
require __DIR__ . '/../../Packages/Libraries/autoload.php';
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SetDependenciesCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'neos:set-dependencies';

    private string $composerPath = 'composer.phar';

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Creates the changelog between two versions.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Create a changelog from two version branches or tags')
            ->addArgument('project', InputArgument::REQUIRED, 'The development-collection prefix, either `flow` or `neos`')
            ->addArgument('branch', InputArgument::REQUIRED, 'The branch')
            ->addArgument('flowBranch', InputArgument::REQUIRED, 'The Flow branch')
            ->addArgument('version', InputArgument::REQUIRED, 'The version to generate the changelog for, e.g. 6.3.0')
            ->addArgument('buildUrl', InputArgument::REQUIRED, 'The buildUrl')
            ->addArgument('composerPath', InputArgument::OPTIONAL, 'The path to the composer.phar', 'composer.phar');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $project = $input->getArgument('project');
        if (!in_array($project, ['flow', 'neos'])) {
            throw new InvalidArgumentException("Project argument needs to be one of `flow` or `neos`.");
        }
        $branch = $input->getArgument('branch');
        $flowBranch = $input->getArgument('flowBranch');
        $version = $input->getArgument('version');
        $buildUrl = $input->getArgument('buildUrl');
        $this->composerPath = $input->getArgument('composerPath');

        $configurationFileName = strtr('dependencies_$project.json', ['$project' => $project]);
        $configurationString = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . $configurationFileName);
        $configuration = json_decode($configurationString, true);

        $this->runConfiguration($configuration, $branch, $flowBranch, $version);

        return Command::SUCCESS;
    }

    protected function runConfiguration(array $configuration, $branch, $flowBranch, $version)
    {
        foreach ($configuration as $workingDirectory => $operations) {
            foreach($operations as $operationName => $packages) {
                match($operationName) {
                    'require' => $this->runRequireConfiguration($packages, $workingDirectory, $branch, $flowBranch, $version),
                    'require-dev' => $this->runRequireDevConfiguration($packages, $workingDirectory, $branch, $flowBranch, $version)
                };
            }
        }
    }

    protected function runRequireConfiguration(array $requires, $workingDirectory, $branch, $flowBranch, $version)
    {
        foreach ($requires as $packageName => $versionTemplate) {
            $dependencyString = $this->buildDependencyString($packageName, $versionTemplate, $branch, $flowBranch, $version);
            $this->updateDependency($workingDirectory, $dependencyString);
        }
    }
    protected function runRequireDevConfiguration(array $requires, $workingDirectory, $branch, $flowBranch, $version)
    {
        foreach ($requires as $packageName => $versionTemplate) {
            $dependencyString = $this->buildDependencyString($packageName, $versionTemplate, $branch, $flowBranch, $version);
            $this->updateDependency($workingDirectory, $dependencyString, true);
        }
    }

    protected function buildDependencyString($packageName, $versionTemplate, $branch, $flowBranch, $version): string
    {
        $versionString = strtr($versionTemplate, [
            '$branch' => $branch,
            '$flowBranch' => $flowBranch,
            '$version' => $version
        ]);
        return sprintf('%s:%s', $packageName, $versionString);
    }

    /**
     * @param string $composerPath
     * @param string $workingDirectory eg. "Distribution" or "Packages/Framework/Neos.Flow"
     * @param string $dependencyString eg. "neos/flow:dev-7.3"
     * @param bool $dev add as dev dependency, defaults to false
     * @return void
     */
    protected function updateDependency(string $workingDirectory, string $dependencyString, bool $dev = false)
    {
        $devFlag = $dev ? '--dev' : '';
        $this->runCliCommand(sprintf('php %s --working-dir=%s require %s --no-update "%s"', $this->composerPath, $workingDirectory, $devFlag, $dependencyString));
    }

    /**
     * @param string $composerPath
     * @param string $workingDirectory
     * @param string $packageComposerName
     * @return void
     */
    protected function removeDependency(string $composerPath, string $workingDirectory, string $packageComposerName)
    {
        $this->runCliCommand(sprintf('php %s --working-dir=%s remove --no-update "%s"', $composerPath, $workingDirectory, $packageComposerName));
    }

    protected function runCliCommand(string $command, $exitOnError = true)
    {
        $output = [];
        $exitCode = null;

        echo ' >> ' . $command . PHP_EOL;
        exec($command, $output, $exitCode);

        if ($exitOnError === true && $exitCode !== 0) {
            echo sprintf('Command "%s" had a problem, exit code %s', $command, $exitCode) . PHP_EOL;
            echo implode(PHP_EOL, $output);
            exit($exitCode);
        }

        return [$exitCode, $output];
    }
}

$application = new Application();
$application->add(new SetDependenciesCommand());
$application->run();
