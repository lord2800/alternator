<?php
namespace Alternator;

use Dynamo\WebApp,
	Dynamo\HttpRequest,
	Dynamo\HttpResponse,
	Dynamo\Middleware;

class Server extends WebApp {
	public function config() {
		if(empty($_ENV['ALTERNATOR_DSN'])) {
			throw new \Exception('Connection string missing');
		}
		$pdo = new \PDO($_ENV['ALTERNATOR_DSN']);
		$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		$pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
		$pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_OBJ);

		$this->injector->provide('pdo', $pdo);
		$this->injector->provide('resource', new Resource($pdo));
		$stderr = fopen('php://stderr', 'a+');
		$this->injector->provide('logger', function () use (&$stderr) {
			return function ($line) use (&$stderr) { fprintf($stderr, $line . PHP_EOL); };
		});

		$this->register(new Middleware\RequestDuration());
		$this->register(new Middleware\CORS(['*'], true));

		$this->router->get('*', function (Resource $resource, HttpRequest $request, HttpResponse $response) {
			$parts = explode('/', substr($request->getUrl(), 1));
			$name = $parts[0];
			$id = 0;
			if(key_exists(1, $parts)) {
				$id = $parts[1];
			}
			$result = empty($id) ? $resource->retrieveAll($name) : $resource->retrieveOne($name, ['and' => ['lhs' => 'id', 'op' => '=', 'rhs' => $id]]);
			$response->setBody(json_encode($result, true));
		});
	}
}
