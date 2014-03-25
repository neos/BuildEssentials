<?php

require(__DIR__ . '/Helpers.php');

foreach ($projects as $identifier => $projectData) {
	// remove crowdin.yaml
	$configurationfilePathAndFilename = realpath(__DIR__ . '/../../../' . $projectData['path'] . '/crowdin.yaml');
	if (file_exists($configurationfilePathAndFilename)) {
		unlink($configurationfilePathAndFilename);
		echo 'Removed ' . $configurationfilePathAndFilename . PHP_EOL;
	}
}
