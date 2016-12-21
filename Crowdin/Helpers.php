<?php

if (!isset($argv[1])) {
    echo 'The configuration file must be given as parameter.' . PHP_EOL;
    exit(1);
}

if (!file_exists($argv[1])) {
    echo 'The configuration file ' . $argv[1] . ' does not exist.' . PHP_EOL;
    exit(1);
}

/** @var array $projects */
$projects = json_decode(file_get_contents($argv[1]), true);

if (array_search('--bundle', $argv) !== false) {
    $useBundling = true;
} else {
    $useBundling = false;
    if (isset($projects['__bundle'])) {
        unset($projects['__bundle']);
    }
}
$uploadTranslations = (array_search('--translations', $argv) !== false);

$branch = isset($projects['branch']) ? $projects['branch'] : null;
unset($projects['branch']);

/**
 * Check if the given project exists on Crowdin.
 *
 * @param string $identifier
 * @param string $apiKey
 * @return boolean
 */
function projectExists($identifier, $apiKey)
{
    $projectInfo = @json_decode(file_get_contents(sprintf('http://api.crowdin.net/api/project/%s/info?json&key=%s', $identifier, $apiKey)), true);

    // check if project exists
    if (!is_array($projectInfo) || (isset($projectInfo['success']) && $projectInfo['success'] === false)) {
        return false;
    } else {
        return true;
    }
}

/**
 *
 * @param string $projectPath
 * @param boolean $uploadTranslations
 * @param string $branch
 * @return void
 */
function executeUpload($projectPath, $uploadTranslations, $branch = null)
{
    if (file_exists($projectPath . '/crowdin.yaml')) {
        if ($branch === null) {
            echo sprintf('cd %s; crowdin-cli upload sources', escapeshellarg($projectPath)) . PHP_EOL;
            passthru(sprintf('cd %s; crowdin-cli upload sources', escapeshellarg($projectPath)));
            if ($uploadTranslations === true) {
                echo sprintf('cd %s; crowdin-cli upload translations', escapeshellarg($projectPath)) . PHP_EOL;
                passthru(sprintf('cd %s; crowdin-cli upload translations', escapeshellarg($projectPath)));
            }
        } else {
            echo sprintf('cd %s; crowdin-cli upload sources -b %s', escapeshellarg($projectPath), escapeshellarg($branch)) . PHP_EOL;
            passthru(sprintf('cd %s; crowdin-cli upload sources -b %s', escapeshellarg($projectPath), escapeshellarg($branch)));
            if ($uploadTranslations === true) {
                echo sprintf('cd %s; crowdin-cli upload translations -b %s', escapeshellarg($projectPath), escapeshellarg($branch)) . PHP_EOL;
                passthru(sprintf('cd %s; crowdin-cli upload translations -b %s', escapeshellarg($projectPath), escapeshellarg($branch)));
            }
        }
    } else {
        echo 'Project is not configured.' . PHP_EOL;
    }
}

/**
 *
 * @param string $projectPath
 * @param string $branch
 * @return void
 */
function executeDownload($projectPath, $branch = null)
{
    if (file_exists($projectPath . '/crowdin.yaml')) {
        if ($branch === null) {
            echo sprintf('cd %s; crowdin-cli download', escapeshellarg($projectPath)) . PHP_EOL;
            passthru(sprintf('cd %s; crowdin-cli download', escapeshellarg($projectPath)));
        } else {
            echo sprintf('cd %s; crowdin-cli download -b %s', escapeshellarg($projectPath), escapeshellarg($branch)) . PHP_EOL;
            passthru(sprintf('cd %s; crowdin-cli download -b %s', escapeshellarg($projectPath), escapeshellarg($branch)));
        }
    } else {
        echo 'Project is not configured.' . PHP_EOL;
    }
}
