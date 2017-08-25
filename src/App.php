<?php

namespace LightMoon;

use Pimple\Container;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;
use FastRoute\Dispatcher\GroupCountBased;
use InvalidArgumentException;

class App
{

    /**
     * @var Container
     */
    private $container;

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
                new Std(),
                new \FastRoute\DataGenerator\GroupCountBased()
            );
        };

        $this->container['router.dispatch'] = function ($c) {
            return new GroupCountBased($c['router.collector']->getData());
        };

        $this->httpServer = new \swoole_http_server($this->host, $this->port);
        $this->httpServer->set($this->container['setting']['server']);
        $this->httpServer->on('request', [$this, 'onRequest']);
    }

    /**
     * @param $request
     * @param $response
     * @return mixed
     */
    public function onRequest($request, $response)
    {
        $httpMethod = $request->server['request_method'];
        $uri = $request->server['request_uri'];

        $routeInfo = $this->container['router.dispatch']->dispatch($httpMethod, $uri);

        if ($routeInfo[0] === Dispatcher::FOUND) {

            $handler = $routeInfo[1]['uses'];
            $middleware = $routeInfo[1]['middleware'];

            if ($handler instanceof Handler) {
                if (is_callable($middleware)) {
                    call_user_func_array($middleware, [$request, $response, $handler]);
                } else {
                    call_user_func_array($handler, [$request, $response]);
                }
            } else {
                throw new InvalidArgumentException('handler is invalid');
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
    }

    /**
     * @param $uri
     * @param $callback
     * @param array $middleware
     */
    public function get($uri, $callback, $middleware = null)
    {
        $this->container['router.collector']->addRoute('GET', $uri, [
            'uses' => new Handler($this->container, $callback),
            'middleware' => $middleware,
        ]);
    }

    /**
     * @param $uri
     * @param $handler
     */
    public function post($uri, $handler)
    {
        $this->container['router.collector']->addRoute('POST', $uri, $handler);
    }

    /**
     * @param $uri
     * @param $handler
     */
    public function put($uri, $handler)
    {
        $this->container['router.collector']->addRoute('PUT', $uri, $handler);
    }

    /**
     * @param $uri
     * @param $handler
     */
    public function delete($uri, $handler)
    {
        $this->container['router.collector']->addRoute('DELETE', $uri, $handler);
    }

    /**
     * @param $uri
     * @param $handler
     */
    public function patch($uri, $handler)
    {
        $this->container['router.collector']->addRoute('PATCH', $uri, $handler);
    }

    /**
     * @param $uri
     * @param $handler
     */
    public function head($uri, $handler)
    {
        $this->container['router.collector']->addRoute('HEAD', $uri, $handler);
    }

    /**
     * @param $uri
     * @param $handler
     */
    public function options($uri, $handler)
    {
        $this->container['router.collector']->addRoute('OPTIONS', $uri, $handler);
    }

    /**
     * @param $method
     * @param $uri
     * @param $handler
     */
    public function addRoute($method, $uri, $handler)
    {
        $this->container['router.collector']->addRoute($method, $uri, $handler);
    }

    /**
     * @param $prefix
     * @param $callback
     */
    public function group($prefix, $callback)
    {
        $this->container['router.collector']->addGroup($prefix, $callback);
    }

    /**
     * @param $provider
     */
    public function register($provider)
    {
        $this->container->register($provider);
    }

    public function run()
    {
        echo "Server running at http://{$this->host}:{$this->port}\n";

        $this->httpServer->start();
    }
}
