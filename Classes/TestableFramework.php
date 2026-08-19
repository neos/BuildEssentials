<?php

/*
 * (c) Contributors of the Neos Project - www.neos.io
 * Please see the LICENSE file which was distributed with this source code.
 */

declare(strict_types=1);

namespace Neos\BuildEssentials;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Core\RequestHandlerInterface;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Utility\Files;

/**
 * Low level operations for use in testing, all to be used in static calls.
 *
 * @Flow\Proxy(false)
 */
abstract readonly class TestableFramework
{
    /**
     * Calculates a FLOW_ROOTPATH
     * @return string
     */
    public static function calculateRootPath(): string
    {
        $flowRootPathFromEnvironment = trim(getenv('FLOW_ROOTPATH') ?: '', '"\' ');
        return $flowRootPathFromEnvironment ?: dirname(__DIR__, 3) . '/';
    }

    public static function getApplicationContext(): string
    {
        $flowContextFromEnvironment = trim(getenv('FLOW_CONTEXT') ?: '', '"\' ');
        return $flowContextFromEnvironment ?: 'Testing';
    }

    /**
     * Build a Flow Bootstrap with an instance of the given class implementing RequestHandlerInterface
     *
     * @param string $context
     * @param class-string<RequestHandlerInterface> $requestHandlerClassname
     * @return Bootstrap
     */
    public static function buildBootstrapWithPreselectedRequestHandler(string $context, string $requestHandlerClassname): Bootstrap
    {
        $flowBootstrap = new Bootstrap($context);
        $flowBootstrap->setPreselectedRequestHandlerClassName($requestHandlerClassname);
        return $flowBootstrap;
    }

    /**
     * Creates an empty table structure according to the active ORM schemas
     *
     * @param Bootstrap $bootstrap
     * @return void
     * @throws \Doctrine\ORM\Tools\ToolsException
     * @throws \Neos\Flow\Exception
     */
    public static function enablePersistence(Bootstrap $bootstrap): void
    {
        $persistenceMangager = $bootstrap->getObjectManager()->get(PersistenceManagerInterface::class);
        if (is_callable([$persistenceMangager, 'compile'])) {
            $result = $persistenceMangager->compile();
            if ($result === false) {
                exit('Could not setup persistence for benchmark');
            }
        }
    }

    /**
     * Drops persistence tables after a test
     *
     * @param Bootstrap $bootstrap
     * @return void
     * @throws \Neos\Flow\Exception
     */
    public static function cleanUpPersistence(Bootstrap $bootstrap): void
    {
        $persistenceManager = $bootstrap->getObjectManager()->get(PersistenceManagerInterface::class);
        // Explicitly call persistAll() so that the "allObjectsPersisted" signal is sent even if persistAll()
        // has not been called during a test. This makes sure that for example certain repositories can clear
        // their internal registry in order to avoid side effects in the following test run.
        // Wrap in try/catch to suppress errors after the actual test is run (e.g. validation)
        try {
            $persistenceManager->persistAll();
        } catch (\Exception $exception) {
        }

        if (is_callable([$persistenceManager, 'tearDown'])) {
            $persistenceManager->tearDown();
        }
    }

    /**
     * Cleans up the directory for storing persistent resources during testing
     *
     * @return void
     * @throws \Exception
     */
    public static function cleanupPersistentResourcesDirectory(Bootstrap $bootstrap): void
    {
        $settings = $bootstrap->getObjectManager()->get(ConfigurationManager::class)->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS);
        foreach ($settings['Neos']['Flow']['resource']['storages'] as $storageName => $storageSettings) {
            if (!isset($storageSettings['storageOptions']['path'])) {
                continue;
            }

            $resourcesStoragePath = $storageSettings['storageOptions']['path'];
            if (!str_contains($resourcesStoragePath, FLOW_PATH_DATA)) {
                throw new \Exception(sprintf('The storage path for persistent resources for the Testing context is "%s" for the "%s" storage, but it must point to a directory below "%s". Please check the Flow settings for the Testing context.', $resourcesStoragePath, $storageName, FLOW_PATH_DATA), 1382018388);
            }
            if (!str_contains($resourcesStoragePath, '/Test/')) {
                throw new \Exception(sprintf('The storage path for persistent resources for the Testing context is "%s" for the "%s" storage, but it must contain "/Test/". Please check the Flow settings for the Testing context.', $resourcesStoragePath, $storageName), 1382018388);
            }
            if (file_exists($resourcesStoragePath)) {
                Files::removeDirectoryRecursively($resourcesStoragePath);
            }
        }
    }
}
