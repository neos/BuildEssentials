#!/usr/bin/env php
<?php
require __DIR__.'/../../Packages/Libraries/autoload.php';
require 'GitLogCommand.php';

use Symfony\Component\Console\Application;

class CreateReleaseNotesCommand extends GitLogCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'neos:create-releasenotes';

    protected function buildLogEntry(array $pr): string
    {
        $logEntry = [(string)$pr['title']];
        $this->addHeadlineMarkup($logEntry, '-');
        $logEntry[] = '';
        $logEntry[] = $this->cleanupPrMessage($pr['body']);
        $logEntry[] = '';
        if (preg_match('/(?:Fixes|Solves|Resolves):? #([0-9]+)/', $pr['body'], $matches) > 0) {
            $issue = $matches[1];
            $logEntry[] = "Related issue: `#$issue <https://github.com/neos/{$this->project}-development-collection/issues/$issue>`_";
            $logEntry[] = '';
        }

        $message = implode("\n", $logEntry);
        $this->output->writeln($message);
        return $message;
    }

    /**
     * Do some more cleanup for release notes
     */
    protected function cleanupPrMessage(string $message): string
    {
        $message = parent::cleanupPrMessage($message);
        $message = preg_replace('/\*\*(?:What I did|How I did it|How to verify it)\*\*[\n\s]+/', '', $message);
        $message = preg_replace('%\* .*?: `#\d+ <https://github.com/neos/[a-z]+-development-collection/issues/\d+>`_%', '', $message);

        return $message;
    }

    protected function getCommitMessage(string $version): string
    {
        $version = $this->getMinorVersionNumber($version);
        return "TASK: Add release notes for $version [skip ci]";
    }

    /**
     * Extract the version number part up to the minor version from a version string.
     * E.g. "7.0.5" => "7.0"
     */
    protected function getMinorVersionNumber(string $version): string
    {
        preg_match('/(\d+\.\d+)/', $version, $matches);
        return $matches[1];
    }

    protected function getHeaderLines(string $prevVersion, string $version): array
    {
        $header = $this->printPreface($this->getMinorVersionNumber($version));
        $header[] = "************
New Features
************

";
        return $header;
    }

    protected function getFooterLines(string $prevVersion, string $version): array
    {
        $footer = $this->printUpgradeInstructions($this->getMinorVersionNumber($prevVersion), $this->getMinorVersionNumber($version));
        return array_merge($footer, $this->printBreakingChanges($this->getMinorVersionNumber($version)));
    }

    protected function printBreakingChanges(string $version): array
    {
        $project = ucfirst($this->project);
        $breakingChanges = ["
****************************
Potentially breaking changes
****************************

$project $version comes with some breaking changes and removes several deprecated
functionalities, be sure to read the following changes and adjust
your code respectively. For a full list of changes please refer
to the change log.
"];
        foreach ($this->orderedMessages as $key => $list) {
            if (substr($key, 0, 2) !== '!!') {
                continue;
            }
            $breakingChanges = array_merge($breakingChanges, $list);
        }
        return count($breakingChanges) > 1 ? $breakingChanges : [];
    }

    protected function printPreface(string $version): array
    {
        $isMajorRelease = substr($version, -2) === '.0';

        $project = ucfirst($this->project);
        return ["========
$project $version
========

This release of $project comes with some great new features, bugfixes and a lot of modernisation of the existing code base.
", "As usual, we worked hard to keep this release as backwards compatible as possible but, since it's a major release, some of the changes might require manual
adjustments. So please make sure to carefully read the upgrade instructions below.
", $isMajorRelease ? "$project $version also increases the minimal required PHP version to **7.3**." : ""];
    }

    protected function printUpgradeInstructions(string $prevVersion, string $version): array
    {
        $dependencyVersionChanges = ""; // TODO

        $versionSlug = str_replace('.', '-', "$prevVersion.$version");
        $versionFile = str_replace('.', '', $version . '0');
        if ($this->project === 'neos') {
            return ["
********************
Upgrade Instructions
********************

See https://docs.neos.io/cms/references/upgrade-instructions/upgrade-instructions-$versionSlug

.. note::

   Additionally all changes in Flow $version apply, see the release notes to further information.
   See https://flowframework.readthedocs.org/en/$version/TheDefinitiveGuide/PartV/ReleaseNotes/$versionFile.html
"];
        }

        return ["
********************
Upgrade Instructions
********************

This section contains instructions for upgrading your Flow **$prevVersion**
based applications to Flow **$version**.
$dependencyVersionChanges
In general just make sure to run the following commands:

To clear all file caches::

 ./flow flow:cache:flush --force

If you have additional cache backends configured, make sure to flush them too.

To apply core migrations::

  ./flow flow:core:migrate <Package-Key>

For every package you have control over (see `Upgrading existing code`_ below).

To validate/fix the database encoding, apply pending migrations and to (re)publish file resources::

 ./flow database:setcharset
 ./flow doctrine:migrate
 ./flow resource:publish

If you are upgrading from a lower version than $prevVersion, be sure to read the
upgrade instructions from the previous Release Notes first.

Upgrading your Packages
=======================

Upgrading existing code
-----------------------

There have been major API changes in Flow $version which require your code to be adjusted. As with earlier changes to Flow
that required code changes on the user side we provide a code migration tool.

Given you have a Flow system with your (outdated) package in place you should run the following before attempting to fix
anything by hand::

 ./flow core:migrate Acme.Demo

This will adjust the package code automatically and/or output further information.
Read the output carefully and manually adjust the code if needed.

To see all the other helpful options this command provides, make sure to run::

 ./flow help core:migrate

Also make sure to read about the `Potentially breaking changes`_ below.

Inside core:migrate
^^^^^^^^^^^^^^^^^^^

The tool roughly works like this:

* Collect all code migrations from packages

* Collect all files from the specified package
* For each migration

  * Check for clean git working copy (otherwise skip it)
  * Check if migration is needed (looks for Migration footers in commit messages)
  * Apply migration and commit the changes

Afterwards you probably get a list of warnings and notes from the
migrations, check those to see if anything needs to be done manually.

Check the created commits and feel free to amend as needed, should
things be missing or wrong. The only thing you must keep in place from
the generated commits is the migration data in ``composer.json``. It is
used to detect if a migration has been applied already, so if you drop
it, things might get out of hands in the future.
"];
    }
}

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

$application = new Application();

$application->add(new CreateChangeLogCommand());
$application->add(new CreateReleaseNotesCommand());

$application->run();
