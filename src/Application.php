<?php

namespace LightMoon;

use Pimple\Container;
use Zend\Config\Config;
use Swoole\Http\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Pimple\ServiceProviderInterface;
use Symfony\Component\Routing\Route;
use LightMoon\Providers\RouteProvider;
use LightMoon\Providers\HttpServerProvider;
use LightMoon\Middleware\PriorityMiddleware;
use LightMoon\Providers\MiddlewareProvider;
use LightMoon\Providers\RequestHandlerProvider;
use Symfony\Component\Routing\RouteCollection;
use LightMoon\Providers\EventDispatcherProvider;

class Application
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var array
     */
    private $events = [];

    /**
     * Application constructor.
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->container = new Container();
        $this->container[Config::class] = $config;
        $this->container->register(new HttpServerProvider());
        $this->container->register(new RouteProvider());
        $this->container->register(new MiddlewareProvider());
        $this->container->register(new RequestHandlerProvider());
        $this->container->register(new EventDispatcherProvider());
        $this->events['request'] = [$this, 'onRequest'];
        $this->events['start'] = [$this, 'onStart'];
    }

    /**
     * @param Server $server
     */
    public function onStart(Server $server)
    {
        echo "Server start at {$server->host}:{$server->port}....\n";
    }

    /**
     * @param $callback
     * @param $priority
     */
    public function middleware(callable $callback, int $priority)
    {
        $this->container[PriorityMiddleware::class]->insert($callback, $priority);
    }

    /**
     * @param $request
     * @param $response
     */
    public function onRequest(Request $request, Response $response)
    {
        $middlewares = clone $this->container[PriorityMiddleware::class];

        foreach ($middlewares as $middleware) {
            $response = $middleware($request, $response);
        }

        $response->end();
    }

    /**
     * @param $name
     * @param $path
     * @param $handler
     * @param array $requirements
     */
    public function get($name, $path, $handler, $requirements = [])
    {
        $this->route($name, $path, $handler, $requirements, ['GET']);
    }

    /**
     * @param $name
     * @param $path
     * @param $handler
     * @param array $requirements
     */
    public function put($name, $path, $handler, $requirements = [])
    {
        $this->route($name, $path, $handler, $requirements, ['PUT']);
    }

    /**
     * @param $name
     * @param $path
     * @param $handler
     * @param array $requirements
     */
    public function delete($name, $path, $handler, $requirements = [])
    {
        $this->route($name, $path, $handler, $requirements, ['DELETE']);
    }

    /**
     * @param $name
     * @param $path
     * @param $handler
     * @param array $requirements
     */
    public function post($name, $path, $handler, $requirements = [])
    {
        $this->route($name, $path, $handler, $requirements, ['POST']);
    }

    /**
     * @param $name
     * @param $path
     * @param $handler
     * @param array $requirements
     */
    public function options($name, $path, $handler, $requirements = [])
    {
        $this->route($name, $path, $handler, $requirements, ['OPTIONS']);
    }

    /**
     * @param $name
     * @param $path
     * @param $handler
     * @param array $requirements
     */
    public function patch($name, $path, $handler, $requirements = [])
    {
        $this->route($name, $path, $handler, $requirements, ['PATCH']);
    }

    /**
     * @param $name
     * @param $path
     * @param $handler
     * @param $requirements
     * @param $methods
     */
    public function route($name, $path, $handler, $requirements, $methods)
    {
        list($controller, $action) = explode('@', $handler);
        $routes = $this->container[RouteCollection::class];
        $route = new Route($path, ['_controller' => $controller, '_action' => $action], $requirements, [], '', [], $methods);
        $routes->add($name, $route);
    }

    /**
     * @param $provider
     */
    public function register(ServiceProviderInterface $provider)
    {
        $this->container->register($provider);
    }

    /**
     * @param $event
     * @param callable $callback
     */
    public function on($event, callable $callback)
    {
        $this->events[$event] = $callback;
    }

    /**
     * @param RouteCollection $group
     */
    public function addRouteGroup(RouteCollection $group)
    {
        $rootRouter = $this->container[RouteCollection::class];
        $rootRouter->addCollection($group);
    }

    public function run()
    {
        $http = $this->container[Server::class];

        foreach ($this->events as $event => $callback) {
            $http->on($event, $callback);
        }

        $http->start();
    }
}
