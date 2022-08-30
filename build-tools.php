#!/usr/bin/env php
<?php
declare(strict_types=1);

require __DIR__.'/../../Packages/Libraries/autoload.php';

require 'GitLogCommand.php';
require 'CreateReleaseNotesCommand.php';
require 'CreateChangeLogCommand.php';

use Symfony\Component\Console\Application;

$application = new Application();

$application->add(new CreateChangeLogCommand());
$application->add(new CreateReleaseNotesCommand());

$application->run();
