<?php

require(__DIR__ . '/Helpers.php');

$projectPath = realpath(__DIR__ . '/../../../');
echo 'Uploading…' . PHP_EOL;
executeUpload($projectPath, $uploadTranslations, $branch);
