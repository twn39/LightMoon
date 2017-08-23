<?php

namespace LightMoon;

use FastRoute\Dispatcher;
use FastRoute\Dispatcher\GroupCountBased;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;
use Pimple\Container;

class App
{

    /**
     * @var Container
     */
    private $container;

    /**
     * @var \React\Http\Server
     */
    private $httpServer;

    private $port;

    private $host;

    /**
     * App constructor.
     * @param array $setting
     */
    public function __construct(array $setting)
    {
        $this->container = new Container();
        $this->container['setting'] = $setting;

        $this->port = $this->container['setting']['listen'];
        $this->host = $this->container['setting']['host'];

        $this->container['router.collector'] = function () {
            /** @var RouteCollector $routeCollector */
            return new RouteCollector(
                new Std(), new \FastRoute\DataGenerator\GroupCountBased()
            );
        };

        $this->container['router.dispatch'] = function ($c) {
            return new GroupCountBased($c['router.collector']->getData());
        };

        $this->httpServer = new \swoole_http_server($this->host, $this->port);
        $this->httpServer->set($this->container['setting']['server']);

        $this->httpServer->on('request', function ($request, $response) {
            $httpMethod = $request->server['request_method'];
            $uri = $request->server['request_uri'];

            $routeInfo = $this->container['router.dispatch']->dispatch($httpMethod, $uri);

            if ($routeInfo[0] === Dispatcher::FOUND) {

                if(is_callable($routeInfo[1])) {
                    return call_user_func_array($routeInfo[1], [$request, $response, $routeInfo[2]]);

                } else {

                    list($class, $method) = explode(':', $routeInfo[1]);

                    $controller = new $class($this->container);

                    return $controller->$method($request, $response, $routeInfo[2]);
                }

            } elseif ($routeInfo[0] === Dispatcher::METHOD_NOT_ALLOWED) {
                $response->status(405);
                $request->end('Method Not Allowed');
                return $response;

            } elseif ($routeInfo[0] === Dispatcher::NOT_FOUND) {
                $response->status(404);
                $response->end('Not Found');
                return $response;
            }

        });

    }

    public function get($uri, $handler) {
        $this->container['router.collector']->addRoute('GET', $uri, $handler);
    }

    public function post($uri, $handler) {
        $this->container['router.collector']->addRoute('POST', $uri, $handler);
    }
    public function put($uri, $handler) {
        $this->container['router.collector']->addRoute('PUT', $uri, $handler);
    }
    public function delete($uri, $handler) {
        $this->container['router.collector']->addRoute('DELETE', $uri, $handler);
    }

    public function patch($uri, $handler) {
        $this->container['router.collector']->addRoute('PATCH', $uri, $handler);
    }
    public function head($uri, $handler) {
        $this->container['router.collector']->addRoute('HEAD', $uri, $handler);
    }

    public function addRoute($method, $uri, $handler) {
        $this->container['router.collector']->addRoute($method, $uri, $handler);
    }

    public function group($prefix, $callback) {
        $this->container['router.collector']->addGroup($prefix, $callback);
    }

    public function run()
    {
        echo "Server running at http://{$this->host}:{$this->port}\n";

        $this->httpServer->start();
    }

}
