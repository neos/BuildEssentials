<?php
namespace TYPO3\FLOW3\Build;

/*                                                                        *
 * This script belongs to the FLOW3 build system.                         *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

@include_once('vfsStream/vfsStream.php');
if (!class_exists('vfsStreamWrapper')) {
	exit(PHP_EOL . 'FLOW3 Bootstrap Error: The unit test bootstrap requires vfsStream to be installed (e.g. via PEAR). Please also make sure that it is accessible via the PHP include path.' . PHP_EOL . PHP_EOL);
}


$composerAutoloader = __DIR__ . '/../../../Packages/Vendor/autoload.php';
if(!file_exists($composerAutoloader)) {
	exit(PHP_EOL . 'FLOW3 Bootstrap Error: The unit test bootstrap requires the autoloader file created at install time by Composer. Looked for "' . $composerAutoloader . '" without success.');
}
require_once($composerAutoloader);

$_SERVER['FLOW3_ROOTPATH'] = dirname(__FILE__) . '/../../../';
$_SERVER['FLOW3_WEBPATH'] = dirname(__FILE__) . '/../../../Web/';
new \TYPO3\FLOW3\Core\Bootstrap('Production');

require_once(FLOW3_PATH_FLOW3 . 'Tests/BaseTestCase.php');
require_once(FLOW3_PATH_FLOW3 . 'Tests/UnitTestCase.php');
require_once(FLOW3_PATH_FLOW3 . 'Classes/TYPO3/FLOW3/Error/Debugger.php');

?>