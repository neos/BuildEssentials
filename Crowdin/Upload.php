<?php

require(__DIR__ . '/Helpers.php');

if ($useBundling === TRUE) {
	$projectPath = realpath(__DIR__ . '/../../../');
	echo 'Working on project bundle' . PHP_EOL;
	executeUpload($projectPath, $uploadTranslations);
} else {
	foreach ($projects as $identifier => $projectData) {
		$projectPath = realpath(__DIR__ . '/../../../' . $projectData['path']);
		echo 'Working on ' . $projectData['name'] . PHP_EOL;
		executeUpload($projectPath, $uploadTranslations);
	}
}