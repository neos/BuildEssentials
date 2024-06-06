<?php

/*
 * (c) Contributors of the Neos Project - www.neos.io
 * Please see the LICENSE file which was distributed with this source code.
 */

declare(strict_types=1);

namespace Neos\Flow\Build;

use Neos\BuildEssentials\TestableFramework;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Testing\RequestHandler\RuntimeSequenceHttpRequestHandler;

$context = TestableFramework::getApplicationContext();

if (preg_match('/^(?:Testing|Testing\/.+)$/', $context) !== 1) {
	die(sprintf('The context "%s" is not allowed. Only "Testing" context or one of its subcontexts "Testing/*" is allowed.', $context));
}

$_SERVER['FLOW_ROOTPATH'] = TestableFramework::calculateRootPath();

require_once($_SERVER['FLOW_ROOTPATH'] . 'Packages/Framework/Neos.Flow/Classes/Core/Bootstrap.php');
$bootstrap = new Bootstrap($context);
$bootstrap->registerRequestHandler(new RuntimeSequenceHttpRequestHandler($bootstrap));
$bootstrap->setPreselectedRequestHandlerClassName(RuntimeSequenceHttpRequestHandler::class);
$bootstrap->run();
