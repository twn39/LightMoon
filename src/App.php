<?php

namespace LightMoon;

use LightMoon\Http\Request;
use LightMoon\Http\Response;
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

    /**
     * @var \swoole_http_server
     */
    private $httpServer;

    /**
     * @var string port
     */
    private $port;

    /**
     * @var string host
     */
    private $host;

    /**
     * @var array events
     */
    private $events = [];

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
        $this->events['request'] = [$this, 'onRequest'];
        $this->events['start'] = [$this, 'onStart'];
    }

    /**
     * @param \swoole_http_server $server
     */
    public function onStart(\swoole_http_server $server)
    {
        echo "Server start at {$server->host}:{$server->port}....";
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

            $psr7Request = Request::fromSwoole($request);
            $psr7Response = new Response();

            if ($handler instanceof Handler) {
                if (is_callable($middleware)) {
                    $psr7Response = call_user_func_array($middleware, [$psr7Request, $psr7Response, $handler]);
                } else {
                    $psr7Response = call_user_func_array($handler, [$psr7Request, $psr7Response]);
                }
                $psr7Response->getBody()->rewind();
                $response->end($psr7Response->getBody()->getContents());
                return $response;
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
     * @param $callback
     * @param null $middleware
     * @internal param $handler
     */
    public function post($uri, $callback, $middleware = null)
    {
        $this->container['router.collector']->addRoute('POST', $uri, [
            'uses' => new Handler($this->container, $callback),
            'middleware' => $middleware,
        ]);
    }

    /**
     * @param $uri
     * @param $callback
     * @param null $middleware
     * @internal param $handler
     */
    public function put($uri, $callback, $middleware = null)
    {
        $this->container['router.collector']->addRoute('PUT', $uri, [
            'uses' => new Handler($this->container, $callback),
            'middleware' => $middleware,
        ]);
    }

    /**
     * @param $uri
     * @param $callback
     * @param null $middleware
     * @internal param $handler
     */
    public function delete($uri, $callback, $middleware = null)
    {
        $this->container['router.collector']->addRoute('DELETE', $uri, [
            'uses' => new Handler($this->container, $callback),
            'middleware' => $middleware,
        ]);
    }

    /**
     * @param $uri
     * @param $callback
     * @param null $middleware
     * @internal param $handler
     */
    public function patch($uri, $callback, $middleware = null)
    {
        $this->container['router.collector']->addRoute('PATCH', $uri, [
            'uses' => new Handler($this->container, $callback),
            'middleware' => $middleware,
        ]);
    }

    /**
     * @param $uri
     * @param $callback
     * @param null $middleware
     * @internal param $handler
     */
    public function head($uri, $callback, $middleware = null)
    {
        $this->container['router.collector']->addRoute('HEAD', $uri, [
            'uses' => new Handler($this->container, $callback),
            'middleware' => $middleware,
        ]);
    }

    /**
     * @param $method
     * @param $uri
     * @param $callback
     * @param null $middleware
     * @internal param $handler
     */
    public function addRoute($method, $uri, $callback, $middleware = null)
    {
        $this->container['router.collector']->addRoute($method, $uri, [
            'uses' => new Handler($this->container, $callback),
            'middleware' => $middleware,
        ]);
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

    /**
     * @param $event
     * @param $callback
     */
    public function on($event, \Closure $callback)
    {
        $this->events[$event] = $callback;
    }

    /**
     * main run
     */
    public function run()
    {
        foreach ($this->events as $event => $callback) {
            $this->httpServer->on($event, $callback);
        }

        $this->httpServer->start();
    }
}
