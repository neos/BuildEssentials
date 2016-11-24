<?php
namespace Neos\Flow\Build;

/*                                                                        *
 * This script belongs to the TYPO3 Flow build system.                    *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

$context = isset($_SERVER['FLOW_CONTEXT']) ? $_SERVER['FLOW_CONTEXT'] : 'Testing';

if (preg_match('/^(?:Testing|Testing\/.+)$/', $context) !== 1) {
	die(sprintf('The context "%s" is not allowed. Only "Testing" context or one of its subcontexts "Testing/*" is allowed.', $context));
}

$_SERVER['FLOW_ROOTPATH'] = dirname(__FILE__) . '/../../../';

if (DIRECTORY_SEPARATOR === '/') {
	// Fixes an issue with the autoloader, see FLOW-183
	shell_exec('cd ' . escapeshellarg($_SERVER['FLOW_ROOTPATH']) . ' && FLOW_CONTEXT=Testing ./flow neos:cache:warmup');
}

require_once($_SERVER['FLOW_ROOTPATH'] . 'Packages/Framework/Neos.Flow/Classes/Core/Bootstrap.php');
$bootstrap = new \Neos\Flow\Core\Bootstrap($context);
$bootstrap->setPreselectedRequestHandlerClassName(\Neos\Flow\Tests\FunctionalTestRequestHandler::class);
$bootstrap->run();
