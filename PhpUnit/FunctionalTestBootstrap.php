<?php
namespace TYPO3\FLOW3\Build;

/*                                                                        *
 * This script belongs to the FLOW3 build system.                         *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

$_SERVER['FLOW3_ROOTPATH'] = dirname(__FILE__) . '/../../../';

require_once($_SERVER['FLOW3_ROOTPATH'] . 'Packages/Framework/TYPO3.FLOW3/Classes/TYPO3/FLOW3/Core/Bootstrap.php');

$bootstrap = new \TYPO3\FLOW3\Core\Bootstrap('Testing');
$bootstrap->setPreselectedRequestHandlerClassName('TYPO3\FLOW3\Tests\FunctionalTestRequestHandler');
$bootstrap->run();

?>