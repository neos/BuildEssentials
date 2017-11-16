<?php

if (!isset($argv[1])) {
    echo 'The configuration file must be given as parameter.' . PHP_EOL;
    exit(1);
}

if (!file_exists($argv[1])) {
    echo 'The configuration file ' . $argv[1] . ' does not exist.' . PHP_EOL;
    exit(1);
}

/** @var array $configuration */
$configuration = json_decode(file_get_contents($argv[1]), true);

if (!isset($configuration['project'])) {
    echo 'No project entry found in configuration file.' . PHP_EOL;
    exit(1);
}

if (!isset($configuration['project']['branch'])) {
    echo 'No project.branch entry found in configuration file.' . PHP_EOL;
    exit(1);
}
$branch = $configuration['project']['branch'];

$verbose = (array_search('--verbose', $argv) !== false);
$uploadTranslations = (array_search('--translations', $argv) !== false);

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
        if ($branch === '') {
            $command = sprintf('cd %s; crowdin upload sources', escapeshellarg($projectPath));
        } else {
            $command = sprintf('cd %s; crowdin upload sources --branch %s', escapeshellarg($projectPath), escapeshellarg($branch));
        }
        echo $command . PHP_EOL;
        passthru($command);

        if ($uploadTranslations === true) {
            if ($branch === '') {
                $command = sprintf('cd %s; crowdin upload translations', escapeshellarg($projectPath));
            } else {
                $command = sprintf('cd %s; crowdin upload translations --branch %s', escapeshellarg($projectPath), escapeshellarg($branch));
            }
            echo $command . PHP_EOL;
            passthru($command);
        }
    } else {
        echo 'Project is not configured. Run setup first.' . PHP_EOL;
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
        if ($branch === '') {
            $command = sprintf('cd %s; crowdin download', escapeshellarg($projectPath));
            echo $command . PHP_EOL;
            passthru($command);
        } else {
            $command = sprintf('cd %s; crowdin download --branch %s', escapeshellarg($projectPath), escapeshellarg($branch));
            echo $command . PHP_EOL;
            passthru($command);
        }
    } else {
        echo 'Project is not configured. Run setup first.' . PHP_EOL;
    }
}
