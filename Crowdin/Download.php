<?php

require(__DIR__ . '/Helpers.php');

$projectPath = realpath(__DIR__ . '/../../../');
echo 'Downloading…' . PHP_EOL;
executeDownload($projectPath, $branch);
