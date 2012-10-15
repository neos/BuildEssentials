<?php
namespace TYPO3\Flow\Build;

/*                                                                        *
 * This script belongs to the TYPO3 Flow build system.                    *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

$composerAutoloader = __DIR__ . '/../../../Packages/Libraries/autoload.php';
if(!file_exists($composerAutoloader)) {
	exit(PHP_EOL . 'TYPO3 Flow Bootstrap Error: The unit test bootstrap requires the autoloader file created at install time by Composer. Looked for "' . $composerAutoloader . '" without success.');
}
require_once($composerAutoloader);

if (!class_exists('org\bovigo\vfs\vfsStream')) {
	exit(PHP_EOL . 'TYPO3 Flow Bootstrap Error: The unit test bootstrap requires vfsStream to be installed. Try "composer update --dev".' . PHP_EOL . PHP_EOL);
}

spl_autoload_register('TYPO3\Flow\Build\loadClassForTesting');

$_SERVER['FLOW_ROOTPATH'] = dirname(__FILE__) . '/../../../';
$_SERVER['FLOW_WEBPATH'] = dirname(__FILE__) . '/../../../Web/';
new \TYPO3\Flow\Core\Bootstrap('Production');

require_once(FLOW_PATH_FLOW . 'Tests/BaseTestCase.php');
require_once(FLOW_PATH_FLOW . 'Tests/UnitTestCase.php');
require_once(FLOW_PATH_FLOW . 'Classes/TYPO3/Flow/Error/Debugger.php');

/**
 * A simple class loader that deals with the Framework classes and is intended
 * for use with unit tests executed by PHPUnit.
 *
 * @param string $className
 * @return void
 */
function loadClassForTesting($className) {
	$classNameParts = explode('\\', $className);
	if (!is_array($classNameParts)) {
		return;
	}

	foreach (new \DirectoryIterator(__DIR__ . '/../../../Packages/') as $fileInfo) {
		if (!$fileInfo->isDir() || $fileInfo->isDot() || $fileInfo->getFilename() === 'Libraries') continue;

		$classFilePathAndName = $fileInfo->getPathname() . '/';
		foreach ($classNameParts as $index => $classNamePart) {
			$classFilePathAndName .= $classNamePart;
			if (file_exists($classFilePathAndName)) {
				break;
			}
			$classFilePathAndName .= '.';
		}

		if (!file_exists($classFilePathAndName . '/Classes')) {
			continue;
		}

		$packageKeyParts = array_slice($classNameParts, 0, $index + 1);
		$classesOrTests = ($classNameParts[$index + 1] === 'Tests' && isset($classNameParts[$index + 2]) && $classNameParts[$index + 2] === 'Unit') ? '/' : '/Classes/' . implode('/', $packageKeyParts) . '/';
		$classesFilePathAndName = $classFilePathAndName . $classesOrTests . implode('/', array_slice($classNameParts, $index + 1)) . '.php';
		if (is_file($classesFilePathAndName)) {
			require($classesFilePathAndName);
			break;
		}
	}
}

?>