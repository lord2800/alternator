<?php
namespace Alternator;

use Dynamo\WebApp,
	Dynamo\HttpRequest,
	Dynamo\HttpResponse,
	Dynamo\Middleware;

class Server extends WebApp {
	private function createPDO() {
		if(empty($_ENV['ALTERNATOR_DSN'])) {
			throw new \Exception('Connection string missing');
		}
		$pdo = new \PDO($_ENV['ALTERNATOR_DSN']);
		$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		$pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
		$pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_OBJ);
		return $pdo;
	}

	private static function determineResource($path) {
		$parts = explode('/', $path);
		return [
			'name' => $parts[1],
			'id' => key_exists(2, $parts) ? $parts[2] : 0
		];
	}

	public function post(Resource $resource, HttpRequest $request, HttpResponse $response) {
		$res = self::determineResource($request->getUrl());
		if(!empty($res['id'])) {
			$response->setStatus(400);
			$response->setBody(json_encode(['message' => 'You must not specify an ID when creating a resource']));
			return;
		}
		if(!$resource->create($res['name'], json_decode($request->getBody()))) {
			$response->setStatus(500);
		}
	}

	public function put(Resource $resource, HttpRequest $request, HttpResponse $response) {
		$res = self::determineResource($request->getUrl());
		if(empty($res['id'])) {
			$response->setStatus(400);
			$response->setBody(json_encode(['message' => 'You must specify an ID when saving a resource']));
		}
		if(!$resource->save($res['name'], json_decode($request->getBody()))) {
			$response->setStatus(500);
		}
	}

	public function delete(Resource $resource, HttpRequest $request, HttpResponse $response) {
		$res = self::determineResource($request->getUrl());
		if(empty($res['id'])) {
			$response->setStatus(400);
			$response->setBody(json_encode(['message' => 'You must specify an ID when deleting a resource']));
		}
		if(!$resource->delete($res['name'], $res['id'])) {
			$response->setStatus(500);
		}
	}

	public function get(Resource $resource, HttpRequest $request, HttpResponse $response) {
		$res = self::determineResource($request->getUrl());
		$result = $resource->retrieve($res['name'], $res['id']);
		if(empty($result) && !empty($res['id'])) {
			$response->setStatus(404);
		}
		// return single responses as an object, and multi-responses as an array--regardless of output count
		$response->setBody(json_encode(!is_array($result) && empty($res['id']) ? [$result] : $result, true));
	}

	public function config() {
		$pdo = $this->createPDO();
		$stderr = fopen('php://stderr', 'a+');

		$this->injector->provide('pdo', $pdo);
		$this->injector->provide('resource', new Resource($pdo));
		$this->injector->provide('logger', function () use (&$stderr) {
			return function ($line) use (&$stderr) { fprintf($stderr, $line . PHP_EOL); };
		});

		$this->register(new Middleware\RequestDuration());
		$this->register(new Middleware\CORS(['*'], true));
		$this->register(function (HttpResponse $response) { yield; $response->setContentType('application/json'); });

		foreach(['get', 'put', 'post', 'delete'] as $method) {
			$this->router->add($method, '*', [$this, $method]);
		}
	}
}
