<?php

/*
 * This file is part of the Neos Flow build system.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Surf\Domain\Model\Workflow;
use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Domain\Model\SimpleWorkflow;

/**
 * Usage
 * =====
 *
 * You will include this file into your Surf deployment configuration if you want to control the
 * deployment using environment variables from e.g. Jenkins. Usually, you will do the following:
 *
 *   include('path/to/Build/BuildEssentials/Surf/CommonJenkinsDistributionBuild.php');
 *
 *   $application->setOption('projectName', 'your-project-name-here');
 *   $application->setOption('repositoryUrl', 'git://git.typo3.org/your-git-url-here.git');
 *
 * furthermore, you can set the following options on $application if needed / desired:
 * - sourceforgeProjectName (needed for sourceforge upload)
 * - sourceforgePackageName (needed for sourceforge upload)
 * - changeLogUri (needed for "TYPO3.Release" plugin on websites)
 * - releaseDownloadLabel (needed for "TYPO3.Release" plugin on websites)
 * - releaseDownloadUriPattern (needed for "TYPO3.Release" plugin on websites)
 *
 * Configuration
 * =============
 *
 * Needs the following mandatory environment options:
 * - WORKSPACE: work-directory
 * - VERSION: version to be packaged
 *
 * By default, the script does the following:
 * - run tests
 * - build .tar.gz, .tar.bz2, .zip distribution
 * - tag the distribution and the submodules
 * - push the tags (if PUSH_TAGS is true)
 *
 * We require the GNU version of the TAR program, so if this script is run f.e. on
 * Mac OS X, you need to install "gnutar" beforehand.
 *
 * Has the following optional environment options:
 * - BRANCH -- the branch to check out as base for further actions, defaults to "master"
 * - SOURCEFORGE_USER -- username which should be used for sourceforge.net upload.
 *                       if set, and ENABLE_SOURCEFORGE_UPLOAD is NOT "false", will upload
 *                       the distribution to sourceforge
 * - ENABLE_SOURCEFORGE_UPLOAD -- if set to string "false", Sourceforge upload is disabled
 *                                no matter if SOURCEFORGE_USER is set or not.
 * - RELEASE_HOST -- the hostname on which to add the release to the TYPO3.Release. If not set
 *                   release creation will be skipped
 * - RELEASE_HOST_LOGIN -- the user to use for the login, optional
 * - RELEASE_HOST_SITE_PATH -- the path in which to run the release commands
 * - ENABLE_TESTS -- if set to string "false", unit and functional tests are disabled
 * - CREATE_TAGS -- if set to string "false", the distribution and submodules are not tagged
 */

$application = new \TYPO3\Surf\Application\FlowDistribution();

if (getenv('VERSION')) {
	$application->setOption('version', getenv('VERSION'));
} else {
	throw new \Exception('version to be released must be set in the VERSION env variable. Example: VERSION=1.0-beta1 or VERSION=1.0.1');
}
if (getenv('BRANCH')) {
	$application->setOption('git-checkout-branch', getenv('BRANCH'));
}

$application->setOption('enableTests', getenv('ENABLE_TESTS') !== 'false');
$application->setOption('createTags', getenv('CREATE_TAGS') !== 'false');
$application->setOption('pushTags', getenv('PUSH_TAGS') === 'true');

if (getenv('SOURCEFORGE_USER') && getenv('ENABLE_SOURCEFORGE_UPLOAD') === 'true') {
	$application->setOption('enableSourceforgeUpload', TRUE);
	$application->setOption('sourceforgeUserName', getenv('SOURCEFORGE_USER'));
}

if (getenv('RELEASE_HOST')) {
	$application->setOption('releaseHost', getenv('RELEASE_HOST'));
	$application->setOption('releaseHostLogin', getenv('RELEASE_HOST_LOGIN'));
	$application->setOption('releaseHostSitePath', getenv('RELEASE_HOST_SITE_PATH'));
}
if (getenv('WORKSPACE')) {
	$application->setDeploymentPath(getenv('WORKSPACE'));
} else {
	throw new \Exception('Deployment path must be set in the WORKSPACE env variable');
}

$deployment->addApplication($application);

$workflow = new SimpleWorkflow();
$deployment->setWorkflow($workflow);

	// Remove the setfilepermissions task because Surf doesn't use sudo ...
	// And we do not need any data or configuration in the release archives ...
$deployment->onInitialize(function() use ($workflow, $application) {
	$workflow->removeTask('typo3.surf:flow:setfilepermissions');
	$workflow->removeTask('typo3.surf:flow:symlinkdata');
	$workflow->removeTask('typo3.surf:flow:symlinkconfiguration');
	$workflow->removeTask('typo3.surf:flow:copyconfiguration');
});

$workflow->setEnableRollback(FALSE);
$node = new Node('localhost');
$node->setHostname('localhost');
$application->addNode($node);
