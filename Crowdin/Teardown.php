<?php

require(__DIR__ . '/Helpers.php');

// remove top-level crowdin.yaml
$configurationfilePathAndFilename = realpath(__DIR__ . '/../../../' . '/crowdin.yaml');
if (file_exists($configurationfilePathAndFilename)) {
    unlink($configurationfilePathAndFilename);
    echo 'Removed ' . $configurationfilePathAndFilename . PHP_EOL;
}

if ($useBundling === true) {
    if (!isset($projects['__bundle'])) {
        echo 'No __bundle configuration found in configuration file.' . PHP_EOL;
        exit(1);
    }
    unset($projects['__bundle']);

    foreach ($projects as $identifier => $projectData) {
        $configurationfilePathAndFilename = realpath(__DIR__ . '/../../../' . $projectData['path'] . '/crowdin.yaml');
        if (file_exists($configurationfilePathAndFilename)) {
            unlink($configurationfilePathAndFilename);
            echo 'Removed ' . $configurationfilePathAndFilename . PHP_EOL;
        }
    }
} else {
    foreach ($projects as $identifier => $projectData) {
        // remove crowdin.yaml in packages
        $configurationfilePathAndFilename = realpath(__DIR__ . '/../../../' . $projectData['path'] . '/crowdin.yaml');
        if (file_exists($configurationfilePathAndFilename)) {
            unlink($configurationfilePathAndFilename);
            echo 'Removed ' . $configurationfilePathAndFilename . PHP_EOL;
        }
    }
}
