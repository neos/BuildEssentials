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

$_SERVER['FLOW_ROOTPATH'] = dirname(__FILE__) . '/../../../';
$_SERVER['FLOW_WEBPATH'] = dirname(__FILE__) . '/../../../Web/';
new \TYPO3\Flow\Core\Bootstrap('Production');

require_once(FLOW_PATH_FLOW . 'Tests/BaseTestCase.php');
require_once(FLOW_PATH_FLOW . 'Tests/UnitTestCase.php');
require_once(FLOW_PATH_FLOW . 'Classes/TYPO3/Flow/Error/Debugger.php');