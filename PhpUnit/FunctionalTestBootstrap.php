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

$_SERVER['FLOW_ROOTPATH'] = dirname(__FILE__) . '/../../../';

require_once($_SERVER['FLOW_ROOTPATH'] . 'Packages/Framework/TYPO3.Flow/Classes/TYPO3/Flow/Core/Bootstrap.php');

$bootstrap = new \TYPO3\Flow\Core\Bootstrap('Testing');
$bootstrap->setPreselectedRequestHandlerClassName('TYPO3\Flow\Tests\FunctionalTestRequestHandler');
$bootstrap->run();

?>