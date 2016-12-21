<?php

require(__DIR__ . '/Helpers.php');

if ($useBundling === true) {
    $projectPath = realpath(__DIR__ . '/../../../');
    echo 'Working on project bundle' . PHP_EOL;
    executeDownload($projectPath, $branch);
} else {
    foreach ($projects as $identifier => $projectData) {
        $projectPath = realpath(__DIR__ . '/../../../' . $projectData['path']);
        echo 'Working on ' . $projectPath . PHP_EOL;
        executeDownload($projectPath, $branch);
    }
}