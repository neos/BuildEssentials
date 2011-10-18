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

/**
 * A simple class loader that deals with the Framework classes and is intended
 * for use with unit tests executed by PHPUnit.
 *
 * PHPUnit offers the possibility to use a "bootstrap" file with every test.
 *
 * This file can be used and will register a simple autoloader so that the files
 * used in the tests can be found.
 *
 * To use PHPUnit with Netbeans create a folder to be used as project test folder
 * and populate it with something like
 *  for i in `ls ../Packages/Framework/` ;
 *    do ln -s ../Packages/Framework/$i/Tests/Unit $i ;
 *  done
 *
 * @param string $className
 * @return void
 */
function loadClassForTesting($className) {
	$classNameParts = explode('\\', $className);
	if (!is_array($classNameParts)) {
		return;
	}
	$indexOfLastClassNamePart = count($classNameParts) - 1;

	foreach (new \DirectoryIterator(__DIR__ . '/../../../Packages/') as $fileInfo) {
		if (!$fileInfo->isDir() || $fileInfo->isDot()) continue;

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

		$classesOrTests = ($classNameParts[$index + 1] === 'Tests' && isset($classNameParts[$index + 2]) && $classNameParts[$index + 2] === 'Unit') ? '/' : '/Classes/';
		$classesFilePathAndName = $classFilePathAndName . $classesOrTests . implode('/', array_slice($classNameParts, $index + 1)) . '.php';
		if (is_file($classesFilePathAndName)) {
			require($classesFilePathAndName);
			break;
		}
	}
}

spl_autoload_register('TYPO3\FLOW3\Build\loadClassForTesting');

$_SERVER['FLOW3_ROOTPATH'] = dirname(__FILE__) . '/../../../';
$_SERVER['FLOW3_WEBPATH'] = dirname(__FILE__) . '/../../../Web/';
new \TYPO3\FLOW3\Core\Bootstrap('Production');

require_once(FLOW3_PATH_FLOW3 . 'Tests/BaseTestCase.php');
require_once(FLOW3_PATH_FLOW3 . 'Tests/UnitTestCase.php');

?>