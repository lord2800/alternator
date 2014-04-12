<?php

require_once 'vendor/autoload.php';

$descriptions = [
	'port' => [
		'format' => function ($value) { return (int)$value; },
		'description' => 'Start server with a specified port'
	],
	'connection' => [
		'format' => function ($value) { return $value; },
		'description' => 'Specify the connection string to use'
	]
];

$opts = [];

try {
	$options = getopt('', ['port','connection:']);

	foreach($options as $name => $opt) {
		if(array_key_exists($name, $descriptions)) {
			$opts[$name] = $descriptions[$name]['format']($opt);
		}
	}

	\Alternator\Server::start($opts);

} catch(Exception $e) {
	printf('Error: ' . $e->getMessage() . PHP_EOL);
	printf('Usage:' . PHP_EOL);
	foreach($descriptions as $name => $params) {
		printf("\t%-16s %s%s", '--'.$name, $params['description'], PHP_EOL);
	}
}
