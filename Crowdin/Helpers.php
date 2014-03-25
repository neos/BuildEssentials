<?php

if (!isset($argv[1])) {
	echo 'The configuration file must be given as parameter.' . PHP_EOL;
	exit(1);
}

if (!file_exists($argv[1])) {
	echo 'The configuration file ' . $argv[1] . ' does not exist.' . PHP_EOL;
	exit(1);
}

$projects = json_decode(file_get_contents($argv[1]), TRUE);
