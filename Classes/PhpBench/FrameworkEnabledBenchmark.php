<?php

/*
 * (c) Contributors of the Neos Project - www.neos.io
 * Please see the LICENSE file which was distributed with this source code.
 */

declare(strict_types=1);

namespace Neos\BuildEssentials\PhpBench;

use Neos\BuildEssentials\TestableFramework;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Testing\RequestHandler\RuntimeSequenceInvokingRequestHandler;

/**
 * Tools for https://github.com/phpbench/phpbench
 */
class FrameworkEnabledBenchmark
{
    protected Bootstrap $flowBootstrap;

    /**
     * Use with phpbench BeforeClassMethods annotation to prepare persistence schema
     */
    public static function enablePersistence(): void
    {
        $_SERVER['FLOW_ROOTPATH'] = TestableFramework::calculateRootPath();
        $bootstrap = TestableFramework::buildBootstrapWithPreselectedRequestHandler(TestableFramework::getApplicationContext(), RuntimeSequenceInvokingRequestHandler::class);
        $bootstrap->run();
        TestableFramework::enablePersistence($bootstrap);
    }

    /**
     * Use with phpbench AfterClassMethods annotation to cleanup persistence schema
     */
    public static function cleanUpPersistence(): void
    {
        $_SERVER['FLOW_ROOTPATH'] = TestableFramework::calculateRootPath();
        $bootstrap = TestableFramework::buildBootstrapWithPreselectedRequestHandler(TestableFramework::getApplicationContext(), RuntimeSequenceInvokingRequestHandler::class);
        $bootstrap->run();
        TestableFramework::cleanUpPersistence($bootstrap);
    }

    /**
     * Makes $this->flowBootstrap available with a running request handler that will have booted up the framework in runtime.
     * This can be used for any test that requires Flow framework features like the ObjectManager, so that
     * the whole booting process will be done before the benchmark measurement.
     * Use with phpbench BeforeMethods annotation
     *
     */
    public function bootstrapWithTestRequestHandler(): void
    {
        $this->withRootPath();
        $this->flowBootstrap = TestableFramework::buildBootstrapWithPreselectedRequestHandler(TestableFramework::getApplicationContext(), RuntimeSequenceInvokingRequestHandler::class);
        $this->flowBootstrap->run();
    }

    /**
     * Writes the FLOW_ROOTPATH global server variable, so it is available globally when starting the framework
     * Use with phpbench BeforeMethods annotation
     */
    public function withRootPath(): void
    {
        $_SERVER['FLOW_ROOTPATH'] = TestableFramework::calculateRootPath();
    }
}
