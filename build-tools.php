#!/usr/bin/env php
<?php
require __DIR__.'/../../Packages/Libraries/autoload.php';

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateChangelogCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'neos:create-changelog';

    /**
     * The development-collection repository prefix to use
     * @var string
     */
    protected $project = 'flow';

    /**
     * The target filename for the changelog
     * @var string
     */
    protected $target;

    /**
     * The file handle for the changelog
     * @var resource
     */
    protected $fp;

    /**
     * @var \GuzzleHttp\Client
     */
    protected $httpClient;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var array[]
     */
    protected $orderedMessages = [
        'SECURITY' => [],
        '!!!\s*FEATURE:' => [],
        'FEATURE:' => [],
        '!!!\s*BUGFIX:' => [],
        'BUGFIX:' => [],
        '!!!\s*TASK:' => [],
        'TASK:' => [],
        '\[?[A-Z]+\]?:' => [] // fallback for any other tag prefixed message
    ];

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Creates the changelog between two versions.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Create a changelog from two version branches or tags')

            ->addArgument('project', InputArgument::REQUIRED, 'The development-collection prefix, either `flow` or `neos`')
            ->addArgument('prevVersion', InputArgument::REQUIRED, 'The previous version, e.g. 6.2.14')
            ->addArgument('version', InputArgument::REQUIRED, 'The version to generate the changelog for, e.g. 6.3.0')
            ->addArgument('target', InputArgument::REQUIRED, 'The target file to write the changelog to')

            ->addOption('buildUrl', null, InputOption::VALUE_OPTIONAL, 'The build URL to use in the commit message')
            ->addOption('githubToken', null,InputOption::VALUE_OPTIONAL, 'To authenticate github calls and avoid API limits')
            ->addOption('filter', null, InputOption::VALUE_OPTIONAL, 'A filter regex to apply to PR titles, e.g. "FEATURE" to only include features')
            ->addOption('nocommit', null, InputOption::VALUE_NONE, 'Specify this if the changelog should not be committed')
        ;
    }

    /**
     * Do some cleanup on the PR message - strip some technical comments, convert links, cleanup newlines and fix escape sequences
     */
    protected function cleanupPrMessage(string $message): string
    {
        # Drop some footer lines from commit messages
        $message = preg_replace('/^Change-Id: (I[a-f0-9]+)$/', '', $message);
        $message = preg_replace('/^Releases?:.*$/', '', $message);
        $message = preg_replace('/^Migration?:.*$/', '', $message);
        $message = preg_replace('/^Reviewed-(by|on)?:.*$/', '', $message);
        $message = preg_replace('/^Tested-by?:.*$/', '', $message);
        $message = preg_replace('/<!--.*?-->\s*/s', '', $message);
        $message = preg_replace('/\*\*Checklist\*\*.*?(- \[.\].*?[\r\n]+)+/s', '', $message);
        $message = preg_replace('/\*\*(?:What I did|How I did it|How to verify it)\*\*[\n\s]+(?=(\*\*|$))/', '', $message);

        # Link issues to Jira
        $message = preg_replace('/(Fixes|Resolves|Related|Relates)?: (NEOS|FLOW)-([0-9]+)/', '* $1: `$2-$3 <https://jira.neos.io/browse/$2-$3>`_', $message);

        # Link issues to GitHub
        $message = preg_replace('/(Fixes|Solves|Resolves|Related(?:\sto)?|Relates|See):? #([0-9]+)/', "* $1: `#$2 <https://github.com/neos/{$this->project}-development-collection/issues/$2>`_", $message);
        $message = preg_replace('/([a-zA-Z0-9]+\/[-.a-zA-Z0-9]+)#([0-9]+)/', '`#$2 <https://github.com/$1/issues/$2>`_', $message);
        $message = preg_replace('/#([0-9]+)\s(?!<http)/', "`#$1 <https://github.com/neos/{$this->project}-development-collection/issues/$1>`_", $message);

        # Link to commits
        $message = preg_replace('/([0-9a-f]{40})/', "`$1 <https://github.com/neos/{$this->project}-development-collection/commit/$1>`_", $message);

        # Convert Markdown links
        $message = preg_replace('/\[([^]]+)\]\(([^)]+)\)/', '`$1 <$2>`_', $message);

        # Convert Markdown single backticks
        $message = preg_replace('/`([^`\n]+)`(?![_`])/', '``$1``', $message);

        # escape backslashes
        $message = preg_replace('/\\\\([^`])/', '\\\\\\\\$1', $message);

        # clean up empty lines
        $message = preg_replace('/\n\n+/', "\n\n", $message);
        $message = preg_replace('/\n+$/', '', $message);

        # join bullet list items
        $message = preg_replace('/(\* [^\n]+)\n+(?=\* [^\n]+)/', "$1\n", $message);

        return $message;
    }

    /**
     * Walk through the affected files in the $mergeCommit, extract the package name from the path and
     * append a line containing a list of those shortened package names in markup like this:
     * * Packages: ``Flow`` ``Utility.Array``
     */
    protected function extractAffectedPackages(array &$lines, string $mergeCommit): void
    {
        $packages = [];
        $changedFiles = explode("\n", shell_exec("git show $mergeCommit^ $mergeCommit^2 --name-only --oneline | tail -n +2"));
        foreach ($changedFiles as $changedFile) {
            $package = explode('/', $changedFile, 2)[0];
            if (is_dir($package)) {
                $packages[] = str_replace('Neos.', '', $package);
            }
        }
        if (count($packages) > 0) {
            $lines[] = '';
            $lines[] = '* Packages: ``' . implode('`` ``', array_unique($packages)) . '``';
        }
    }

    /**
     * Add a line of repeating $underlineCharacter`s that match the length of the last line.
     */
    protected function addHeadlineMarkup(array &$lines, string $underlineCharacter = '='): void
    {
        $lastLine = end($lines);
        if ($lastLine) {
            $lines[] = str_repeat($underlineCharacter, strlen($lastLine));
        }
    }

    /**
     * Build a changelog entry for the given parsed PR info
     */
    protected function buildChangeLogEntry(array $pr): string
    {
        $changeLogEntry = ["\`{$pr['title']} <{$pr['html_url']}>\`_"];
        $this->addHeadlineMarkup($changeLogEntry, '-');
        $changeLogEntry[] = '';
        $changeLogEntry[] = $this->cleanupPrMessage($pr['body']);
        $this->extractAffectedPackages($changeLogEntry, $pr['merge_commit_sha']);
        $changeLogEntry[] = '';
        $message = implode("\n", $changeLogEntry);
        $this->output->writeln($message);
        return $message;
    }

    /**
     * Build a changelog entry from the given PR and append it into the list of ordered messages
     */
    protected function orderChangeLogEntry(?array $pr, ?string $filter): void
    {
        if ($pr === null) {
            return;
        }
        foreach (array_keys($this->orderedMessages) as $titlePrefix) {
            if ($filter !== null && preg_match('/' . $filter .'/', $pr['title']) < 1) {
                continue;
            }
            if (preg_match('/'.$titlePrefix.'/', $pr['title']) > 0) {
                $this->orderedMessages[$titlePrefix][] = $this->buildChangeLogEntry($pr);
                break;
            }
        }
    }

    protected function getOrderedMessages(): array
    {
        return array_merge(...array_values($this->orderedMessages));
    }

    protected function writeChangeLog(array $lines, bool $close = false): void
    {
        if (!$this->fp) {
            $this->fp = fopen($this->target, 'wb+');
        }
        fwrite($this->fp, implode("\n", $lines));
        if ($close === true) {
            fclose($this->fp);
        }
    }

    protected function commitChangeLog(string $version, ?string $buildUrl): void
    {
        $logType = strpos($this->target, 'ReleaseNotes') !== false ? 'release notes' : 'changelog';
        $this->output->writeln(shell_exec("git add {$this->target}"));
        $commitCommand = "git commit -m \"TASK: Add $logType for $version [skip ci]\"";
        if ($buildUrl) {
            $commitCommand .= " -m \"See $buildUrl\"";
        }
        $this->output->writeln(shell_exec($commitCommand . ' || echo " nothing to commit "'));
    }

    protected function fetchPr(string $pullRequest, ?string $githubToken): ?array
    {
        if (!$this->httpClient) {
            $this->httpClient = new GuzzleHttp\Client([
                'headers' => $githubToken ? [
                    'Authorization' => "token $githubToken"
                ] : []
            ]);
        }

        $url = "https://api.github.com/repos/neos/{$this->project}-development-collection/pulls/$pullRequest";
        $this->output->writeln("fetching info from $url");

        try {
            $response = $this->httpClient->get($url);
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            $this->output->writeln("Error fetching PR $pullRequest: " . $e->getMessage());
            return null;
        }
        if ($response->getStatusCode() !== 200) {
            $this->output->writeln("Error fetching PR $pullRequest ({$response->getStatusCode()}): {$response->getBody()->getContents()}");
            return null;
        }
        return json_decode($response->getBody()->getContents(), true);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $prevVersion = $input->getArgument('prevVersion');
        $version = $input->getArgument('version');
        // "Neos.Flow/Documentation/TheDefinitiveGuide/PartV/ChangeLogs/$version.rst"
        $this->target = $input->getArgument('target');

        $githubToken = $input->getOption('githubToken');
        $buildUrl = $input->getOption('buildUrl');
        $filter = $input->getOption('filter');

        chdir('Packages/Framework');

        $date = date("Y-m-d");
        $header = ["\`$version ($date) <https://github.com/neos/{$this->project}-development-collection/releases/tag/$version>\`_"];
        $this->addHeadlineMarkup($header, '=');
        $header[] = '';
        $header[] = "Overview of merged pull requests";
        $this->addHeadlineMarkup($header, '~');
        $header[] = '';
        $header[] = '';
        $this->writeChangeLog($header);

        $gitLog = explode("\n", shell_exec("git log $prevVersion..$(git tag -l $version) --grep=\"^Merge pull request\" --oneline | cut -d ' ' -f1"));
        foreach ($gitLog as $mergeCommit) {
            if (!$mergeCommit) {
                continue;
            }
            $pullRequest = trim(shell_exec("git show $mergeCommit --no-patch --oneline | cut -d ' ' -f5 | cut -c2-"));

            if (!is_numeric($pullRequest)) {
                $this->output->writeln("Ignoring Merge $mergeCommit as it referenced an invalid PR #$pullRequest");
                continue;
            }
            $pr = $this->fetchPr($pullRequest, $githubToken);
            $this->orderChangeLogEntry($pr, $filter);
        }
        $this->writeChangeLog($this->getOrderedMessages());

        $footer = [
            '',
            "\`Detailed log <https://github.com/neos/{$this->project}-development-collection/compare/$prevVersion...$version>\`_"
        ];
        $this->addHeadlineMarkup($footer, '~');
        $footer[] = '';
        $this->writeChangeLog($footer, true);

        if (!$input->getOption('nocommit')) {
            $this->commitChangeLog($version, $buildUrl);
        }
        return Command::SUCCESS;
    }
}

$application = new Application();

$application->add(new CreateChangelogCommand());

$application->run();
