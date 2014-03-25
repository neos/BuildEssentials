<?php

require(__DIR__ . '/Helpers.php');

foreach ($projects as $identifier => $projectData) {
	$projectInfo = json_decode(file_get_contents(sprintf('http://api.crowdin.net/api/project/%s/info?json&key=%s', $identifier, $projectData['apiKey'])), TRUE);

	// check if project exists
	if (isset($projectInfo['success']) && $projectInfo['success'] === FALSE) {
		echo 'Project ' . $projectData['name'] . ' does not exist, please create it.' . PHP_EOL;
		continue;
	}

	$projectPath = realpath(__DIR__ . '/../../../' . $projectData['path']);
	if (!file_exists($projectPath . '/crowdin.yaml')) {
		echo 'Project ' . $projectData['name'] . ' is not configured, skipping.' . PHP_EOL;
		continue;
	}

	echo 'Working on ' . $projectPath . PHP_EOL;
	passthru(sprintf('cd %s; crowdin-cli upload sources', escapeshellarg($projectPath)));
	if (isset($argv[2]) && $argv[2] === '--translations') {
		passthru(sprintf('cd %s; crowdin-cli upload translations', escapeshellarg($projectPath)));
	}
}
