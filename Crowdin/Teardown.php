<?php

require(__DIR__ . '/Helpers.php');

$configurationPathAndFilename = realpath(__DIR__ . '/../../../' . '/crowdin.yaml');
if (file_exists($configurationPathAndFilename)) {
    unlink($configurationPathAndFilename);
    echo 'Removed ' . $configurationPathAndFilename . PHP_EOL;
}
