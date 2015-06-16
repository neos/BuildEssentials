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
$projects = json_decode(file_get_contents($argv[1]), TRUE);

if (array_search('--bundle', $argv) !== FALSE) {
	$useBundling = TRUE;
} else {
	$useBundling = FALSE;
	if (isset($projects['__bundle'])) {
		unset($projects['__bundle']);
	}
}
$uploadTranslations = (array_search('--translations', $argv) !== FALSE);

/**
 * Check if the given project exists on Crowdin.
 *
 * @param string $identifier
 * @param string $apiKey
 * @return boolean
 */
function projectExists($identifier, $apiKey) {
	$projectInfo = json_decode(file_get_contents(sprintf('http://api.crowdin.net/api/project/%s/info?json&key=%s', $identifier, $apiKey)), TRUE);

	// check if project exists
	if (isset($projectInfo['success']) && $projectInfo['success'] === FALSE) {
		return FALSE;
	} else {
		return TRUE;
	}
}

/**
 *
 * @param string $projectPath
 * @param boolean $uploadTranslations
 * @return void
 */
function executeUpload($projectPath, $uploadTranslations) {
	if (file_exists($projectPath . '/crowdin.yaml')) {
		echo sprintf('cd %s; crowdin-cli upload sources', escapeshellarg($projectPath)) . PHP_EOL;
		passthru(sprintf('cd %s; crowdin-cli upload sources', escapeshellarg($projectPath)));
		if ($uploadTranslations === TRUE) {
			echo sprintf('cd %s; crowdin-cli upload translations', escapeshellarg($projectPath)) . PHP_EOL;
			passthru(sprintf('cd %s; crowdin-cli upload translations', escapeshellarg($projectPath)));
		}
	} else {
		echo 'Project is not configured.' . PHP_EOL;
	}
}

/**
 *
 * @param string $projectPath
 * @return void
 */
function executeDownload($projectPath) {
	if (file_exists($projectPath . '/crowdin.yaml')) {
		echo sprintf('cd %s; crowdin-cli download', escapeshellarg($projectPath)) . PHP_EOL;
		passthru(sprintf('cd %s; crowdin-cli download', escapeshellarg($projectPath)));
	} else {
		echo 'Project is not configured.' . PHP_EOL;
	}
}
