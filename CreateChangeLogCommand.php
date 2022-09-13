<?php
declare(strict_types=1);

class CreateChangeLogCommand extends GitLogCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'neos:create-changelog';

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
     * Build a changelog entry for the given parsed PR info
     */
    protected function buildLogEntry(array $pr): string
    {
        $changeLogEntry = ["`{$pr['title']} <{$pr['html_url']}>`_"];
        $this->addHeadlineMarkup($changeLogEntry, '-');
        $changeLogEntry[] = '';
        $changeLogEntry[] = $this->cleanupPrMessage((string)$pr['body']);
        $this->extractAffectedPackages($changeLogEntry, $pr['merge_commit_sha']);
        $changeLogEntry[] = '';
        $message = implode("\n", $changeLogEntry);
        $this->output->writeln($message);
        return $message;
    }

    protected function getCommitMessage(string $version): string
    {
        return "TASK: Add changelog for $version [skip ci]";
    }

    protected function getHeaderLines(string $prevVersion, string $version): array
    {
        $date = date("Y-m-d");
        $header = ["`$version ($date) <https://github.com/neos/{$this->project}-development-collection/releases/tag/$version>`_"];
        $this->addHeadlineMarkup($header, '=');
        $header[] = '';
        $header[] = "Overview of merged pull requests";
        $this->addHeadlineMarkup($header, '~');
        $header[] = '';
        $header[] = '';
        return $header;
    }

    protected function getFooterLines(string $prevVersion, string $version): array
    {
        $footer = [
            '',
            "`Detailed log <https://github.com/neos/{$this->project}-development-collection/compare/$prevVersion...$version>`_"
        ];
        $this->addHeadlineMarkup($footer, '~');
        $footer[] = '';
        return $footer;
    }
}
