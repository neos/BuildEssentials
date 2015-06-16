<?php

require(__DIR__ . '/Helpers.php');

// remove top-level crowdin.yaml
$configurationfilePathAndFilename = realpath(__DIR__ . '/../../../' . '/crowdin.yaml');
if (file_exists($configurationfilePathAndFilename)) {
	unlink($configurationfilePathAndFilename);
	echo 'Removed ' . $configurationfilePathAndFilename . PHP_EOL;
}

foreach ($projects as $identifier => $projectData) {
	// remove crowdin.yaml in packages
	$configurationfilePathAndFilename = realpath(__DIR__ . '/../../../' . $projectData['path'] . '/crowdin.yaml');
	if (file_exists($configurationfilePathAndFilename)) {
		unlink($configurationfilePathAndFilename);
		echo 'Removed ' . $configurationfilePathAndFilename . PHP_EOL;
	}
}
