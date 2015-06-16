<?php

require(__DIR__ . '/Helpers.php');

$configurationFileTemplate = file_get_contents(__DIR__ . '/template-crowdin.yaml');
$filesEntryTemplate = file_get_contents(__DIR__ . '/template-filesentry.yaml');

if ($useBundling === TRUE) {
	if (!isset($projects['__bundle'])) {
		echo 'No __bundle configuration found in configuration file.' . PHP_EOL;
		exit(1);
	}

	$bundleProjectIdentifier = $projects['__bundle']['projectIdentifier'];
	$bundleProjectApiKey = $projects['__bundle']['apiKey'];
	unset($projects['__bundle']);

	if (projectExists($bundleProjectIdentifier, $bundleProjectApiKey) === FALSE) {
		echo 'Project ' . $bundleProjectIdentifier . ' does not exist, please create it in Crowdin.' . PHP_EOL;
		exit(1);
	}

	$filesEntryContent = '';
	foreach ($projects as $identifier => $projectData) {
		$projectPath = '/' . $projectData['path'];
		$filesEntryContent .= sprintf($filesEntryTemplate, $projectPath, $projectPath);
	}
	$configurationFileContent = sprintf($configurationFileTemplate, $bundleProjectIdentifier, $bundleProjectApiKey, $filesEntryContent);

	$configurationfilePathAndFilename = realpath(__DIR__ . '/../../../') . '/crowdin.yaml';
	file_put_contents($configurationfilePathAndFilename, $configurationFileContent);
	echo 'Wrote ' . $configurationfilePathAndFilename . PHP_EOL;
} else {
	foreach ($projects as $identifier => $projectData) {
		if (!isset($projectData['apiKey'])) {
			echo 'Project ' . $projectData['name'] . ' has no apiKey set.' . PHP_EOL;
			continue;
		}

		if (projectExists($identifier, $projectData['apiKey']) === FALSE) {
			echo 'Project ' . $projectData['name'] . ' does not exist, please create it in Crowdin.' . PHP_EOL;
			continue;
		}

		$filesEntryContent = sprintf($filesEntryTemplate, '', '');
		$configurationFileContent = sprintf($configurationFileTemplate, $identifier, $projectData['apiKey'], $filesEntryContent);

		$configurationfilePathAndFilename = realpath(__DIR__ . '/../../../' . $projectData['path']) . '/crowdin.yaml';
		file_put_contents($configurationfilePathAndFilename, $configurationFileContent);
		echo 'Wrote ' . $configurationfilePathAndFilename . PHP_EOL;
	}
}

