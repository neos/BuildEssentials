<?php
/*
 * This script belongs to the Flow build system.
 *
 * This is a simple filter to remove any requirements that are
 * simply specifying a stability flag for a package in the typo3
 * vendor namespace.
 */
$manifest = json_decode(file_get_contents('composer.json'), TRUE);

if (isset($manifest['require'])) {
	foreach ($manifest['require'] as $key => $requirement) {
		if (strpos($key, 'typo3/') === 0 && $requirement[0] === '@') {
			unset($manifest['require'][$key]);
		}
	}
}
if (isset($manifest['require-dev'])) {
	foreach ($manifest['require-dev'] as $key => $requirement) {
		if (strpos($key, 'typo3/') === 0 && $requirement[0] === '@') {
			unset($manifest['require-dev'][$key]);
		}
	}
}

if (version_compare(PHP_VERSION, '5.4.0', '<')) {
	file_put_contents('composer.json', json_encode($manifest));
} else {
	file_put_contents('composer.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}
