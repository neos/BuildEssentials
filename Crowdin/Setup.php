<?php

require(__DIR__ . '/Helpers.php');

$configurationFileTemplate = file_get_contents(__DIR__ . '/template-crowdin.yaml');
$filesEntryTemplate = file_get_contents(__DIR__ . '/template-filesentry.yaml');

if ($useBundling === true) {
    if (!isset($projects['__bundle'])) {
        echo 'No __bundle configuration found in configuration file.' . PHP_EOL;
        exit(1);
    }

    $bundleProjectIdentifier = $projects['__bundle']['projectIdentifier'];
    $apiKey = $projects['__bundle']['apiKey'];
    $apiKeyEnvironmentVariable = false;
    if (preg_match('/^%.+%$/', $apiKey) === 1) {
        $apiKeyEnvironmentVariable = trim($apiKey, '%');
        $apiKey = getenv($apiKeyEnvironmentVariable);
    }
    unset($projects['__bundle']);

    if (projectExists($bundleProjectIdentifier, $apiKey) === false) {
        echo 'Project ' . $bundleProjectIdentifier . ' does not exist, please create it in Crowdin.' . PHP_EOL;
        exit(1);
    }

    $filesEntryContent = '';
    foreach ($projects as $identifier => $projectData) {
        $projectPath = '/' . $projectData['path'];
        $filesEntryContent .= sprintf($filesEntryTemplate, $projectPath, $projectPath);
    }
    $configurationFileContent = sprintf($configurationFileTemplate, $bundleProjectIdentifier, $apiKeyEnvironmentVariable ? '_env' : '', $apiKeyEnvironmentVariable ?: $apiKey, $filesEntryContent);

    $configurationfilePathAndFilename = realpath(__DIR__ . '/../../../') . '/crowdin.yaml';
    file_put_contents($configurationfilePathAndFilename, $configurationFileContent);
    echo 'Wrote ' . $configurationfilePathAndFilename . PHP_EOL;
} else {
    foreach ($projects as $identifier => $projectData) {
        if (!isset($projectData['apiKey'])) {
            echo 'Project ' . $projectData['name'] . ' has no apiKey set.' . PHP_EOL;
            continue;
        }

        $apiKey = $projectData['apiKey'];
        $apiKeyEnvironmentVariable = false;
        if (preg_match('/^%.+%$/', $apiKey) === 1) {
            $apiKeyEnvironmentVariable = trim($apiKey, '%');
            $apiKey = getenv($apiKeyEnvironmentVariable);
        }

        if (projectExists($identifier, $apiKey) === false) {
            echo 'Project ' . $projectData['name'] . ' does not exist, please create it in Crowdin.' . PHP_EOL;
            continue;
        }

        $filesEntryContent = sprintf($filesEntryTemplate, '', '');
        $configurationFileContent = sprintf($configurationFileTemplate, $identifier, $apiKeyEnvironmentVariable ? '_env' : '', $apiKeyEnvironmentVariable ?: $apiKey, $filesEntryContent);

        $configurationfilePathAndFilename = realpath(__DIR__ . '/../../../' . $projectData['path']) . '/crowdin.yaml';
        file_put_contents($configurationfilePathAndFilename, $configurationFileContent);
        echo 'Wrote ' . $configurationfilePathAndFilename . PHP_EOL;
    }
}

