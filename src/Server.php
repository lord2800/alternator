<?php
namespace Alternator;

class Server {
	public static function start(array $options) {
		if(empty($options['connection'])) {
			throw new \Exception('Connection string missing');
		}
	}
}
