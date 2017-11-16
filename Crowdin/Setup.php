<?php

require(__DIR__ . '/Helpers.php');

$configurationFileTemplate = file_get_contents(__DIR__ . '/template-crowdin.yaml');
$filesEntryTemplate = file_get_contents(__DIR__ . '/template-filesentry.yaml');

$projectIdentifier = $configuration['project']['identifier'];
$apiKey = $configuration['project']['apiKey'];
$apiKeyEnvironmentVariable = false;
if (preg_match('/^%.+%$/', $apiKey) === 1) {
    $apiKeyEnvironmentVariable = trim($apiKey, '%');
    $apiKey = getenv($apiKeyEnvironmentVariable);
}

if (projectExists($projectIdentifier, $apiKey) === false) {
    echo 'Project ' . $projectIdentifier . ' does not exist, please create it in Crowdin.' . PHP_EOL;
    exit(1);
}

$filesEntryContent = [];
foreach ($configuration['items'] as $itemKey => $itemConfiguration) {
    foreach (glob($itemConfiguration['path'], GLOB_ONLYDIR) as $projectPath) {
        $translationSourceDirectory = $projectPath . '/Resources/Private/Translations/en';
        if (!is_dir($translationSourceDirectory)) {
            if ($verbose) {
                echo sprintf(
                    'Skipping "%s" since no XLIFF sources exist.' . PHP_EOL,
                    basename($projectPath),
                    $translationSourceDirectory
                );
            }
            continue;
        }

        $filesEntryContent[] .= rtrim(sprintf($filesEntryTemplate, $projectPath, $projectPath));
    }
}

$configurationFileContent = sprintf(
    $configurationFileTemplate,
    $projectIdentifier,
    $apiKeyEnvironmentVariable ? '_env' : '',
    $apiKeyEnvironmentVariable ?: $apiKey,
    implode(',' . chr(10), $filesEntryContent)
);

$configurationPathAndFilename = realpath(__DIR__ . '/../../../') . '/crowdin.yaml';
file_put_contents($configurationPathAndFilename, $configurationFileContent);

echo 'Wrote ' . $configurationPathAndFilename . PHP_EOL;
