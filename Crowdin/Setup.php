<?php

require(__DIR__ . '/Helpers.php');

$configurationFileTemplate = file_get_contents(__DIR__ . '/template-crowdin.yaml');

foreach ($projects as $identifier => $projectData) {
	$projectInfo = json_decode(file_get_contents(sprintf('http://api.crowdin.net/api/project/%s/info?json&key=%s', $identifier, $projectData['apiKey'])), TRUE);

	// check if project exists
	if (isset($projectInfo['success']) && $projectInfo['success'] === FALSE) {
		echo 'Project ' . $projectData['name'] . ' does not exist, please create it.' . PHP_EOL;
		continue;
	}

	// create crowdin.yaml
	$configurationfilePathAndFilename = realpath(__DIR__ . '/../../../' . $projectData['path']) . '/crowdin.yaml';
	$configurationFileContent = sprintf($configurationFileTemplate, $identifier, $projectData['apiKey']);
	file_put_contents($configurationfilePathAndFilename, $configurationFileContent);
	echo 'Wrote ' . $configurationfilePathAndFilename . PHP_EOL;
}
