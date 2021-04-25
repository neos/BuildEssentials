<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class GitLogCommand extends Command
{
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
     * Add a line of repeating $underlineCharacter`s that match the length of the last line.
     */
    protected function addHeadlineMarkup(array &$lines, string $underlineCharacter = '='): void
    {
        $lastLine = end($lines);
        if ($lastLine) {
            $lines[] = str_repeat($underlineCharacter, strlen($lastLine));
        }
    }

    abstract protected function buildLogEntry(array $pr);

    /**
     * Build a changelog entry from the given PR and append it into the list of ordered messages
     */
    protected function orderLogEntry(?array $pr, ?string $filter): void
    {
        if ($pr === null) {
            return;
        }
        foreach (array_keys($this->orderedMessages) as $titlePrefix) {
            if ($filter !== null && preg_match('/' . $filter .'/', $pr['title']) < 1) {
                continue;
            }
            if (preg_match('/'.$titlePrefix.'/', $pr['title']) > 0) {
                $this->orderedMessages[$titlePrefix][] = $this->buildLogEntry($pr);
                break;
            }
        }
    }

    protected function getOrderedMessages(): array
    {
        return array_merge(...array_values($this->orderedMessages));
    }

    protected function writeLogLines(array $lines, bool $close = false): void
    {
        if (!$this->fp) {
            $this->fp = fopen($this->target, 'wb+');
            if ($this->fp === false) {
                throw new Error("Error opening {$this->target} for writing.");
            }
            $this->output->writeln(getcwd());
            $this->output->writeln("Created {$this->target}.");
        }
        fwrite($this->fp, implode("\n", $lines));
        if ($close === true) {
            fclose($this->fp);
            $this->fp = null;
            $this->output->writeln("Wrote {$this->target}.");
        }
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

    abstract protected function getCommitMessage(string $version): string;

    protected function commitLog(string $message, ?string $buildUrl): void
    {
        $this->output->writeln(shell_exec("git add {$this->target}"));
        $commitCommand = "git commit -m \"$message\"";
        if ($buildUrl) {
            $commitCommand .= " -m \"See $buildUrl\"";
        }
        $this->output->writeln(shell_exec($commitCommand . ' || echo " nothing to commit "'));
    }

    abstract protected function getHeaderLines(string $prevVersion, string $version): array;
    abstract protected function getFooterLines(string $prevVersion, string $version): array;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $this->project = $input->getArgument('project');
        if (!in_array($this->project, ['flow', 'neos'])) {
            throw new InvalidArgumentException("Project argument needs to be one of `flow` or `neos`.");
        }
        $prevVersion = $input->getArgument('prevVersion');
        $version = $input->getArgument('version');
        // "Neos.Flow/Documentation/TheDefinitiveGuide/PartV/ChangeLogs/$version.rst"
        $this->target = $input->getArgument('target');

        $githubToken = $input->getOption('githubToken');
        $buildUrl = $input->getOption('buildUrl');
        $filter = $input->getOption('filter');

        chdir($this->project === 'flow' ? 'Packages/Framework' : 'Packages/Neos');

        $this->writeLogLines($this->getHeaderLines($prevVersion, $version));

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
            $this->orderLogEntry($pr, $filter);
        }
        $this->writeLogLines($this->getOrderedMessages());

        $this->writeLogLines($this->getFooterLines($prevVersion, $version), true);

        if (!$input->getOption('nocommit')) {
            $this->commitLog($this->getCommitMessage($version), $buildUrl);
        }
        return Command::SUCCESS;
    }
}
