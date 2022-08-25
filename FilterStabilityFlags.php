<?php
/*
 * This script belongs to the Flow build system.
 *
 * This is a simple filter to remove any requirements that are
 * simply specifying a stability flag for a package in the typo3
 * vendor namespace.
 */
$manifest = json_decode(file_get_contents('composer.json'), true, 512, JSON_THROW_ON_ERROR);

if (isset($manifest['require'])) {
    foreach ($manifest['require'] as $key => $requirement) {
        if ($requirement[0] === '@' && strpos($key, 'typo3/') === 0) {
            unset($manifest['require'][$key]);
        }
    }
}
if (isset($manifest['require-dev'])) {
    foreach ($manifest['require-dev'] as $key => $requirement) {
        if ($requirement[0] === '@' && strpos($key, 'typo3/') === 0) {
            unset($manifest['require-dev'][$key]);
        }
    }
}

file_put_contents('composer.json', json_encode($manifest, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
