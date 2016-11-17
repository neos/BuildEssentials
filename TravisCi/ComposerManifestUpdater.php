#!/usr/bin/env php
<?php

if (!is_file('composer.json')) {
    echo('There is no ".composer.json" template file in the current directory "' . getcwd() . '", make sure you are in the right directory and a template exists.');
    exit(1);
}

$travisRepoSlug = getenv('TRAVIS_REPO_SLUG');
$targetRepository = getenv('NEOS_TARGET_REPOSITORY');
$targetVersion = getenv('NEOS_TARGET_VERSION');

if ($travisRepoSlug === FALSE || $targetRepository === FALSE || $targetVersion === FALSE) {
    echo('ENV variables TRAVIS_REPO_SLUG, NEOS_TARGET_REPOSITORY or NEOS_TARGET_VERSION are not set');
    exit(1);
}

$composerManifest = json_decode(file_get_contents('composer.json'), true);

if(!array_key_exists('repositories', $composerManifest)) {
    $composerManifest['repositories'] = [];
}
$composerManifest['repositories'][] = [
    'type' => 'git',
    'url' => '../../' . $travisRepoSlug
];

// Refactor target version
if(strpos($targetVersion, '.')) {
    $targetVersion = rtrim($targetVersion, '.') . '.x-dev';
} else {
    $targetVersion = 'dev-' . $targetVersion;
}

// replace dev-collection
if(isset($composerManifest['require'][$targetRepository])) {
    // We create a local branch "travis" in the travis.yml of the specific repository and use this for correct composer loading of the PR branch.
    $composerManifest['require'][$targetRepository] = 'dev-travis' . ' as ' . $targetVersion;
} else {
    echo('The package ' . $targetRepository . ' could not be found in composers require section');
    exit(1);
}

$output = json_encode($composerManifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
print_r($output);
file_put_contents('composer.json', $output);
exit(0);
