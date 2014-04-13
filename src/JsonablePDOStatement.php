<?php
namespace Alternator;

class JsonablePDOStatement extends \PDOStatement implements \JsonSerializable {
	protected function __construct() {}
	public function jsonSerialize() {
		$results = [];
		foreach($this as $row) {
			$results[] = $row;
		}
		// if only one row, return it directly
		if(count($results) === 1) {
			return $results[0];
		}
		return $results;
	}
}
