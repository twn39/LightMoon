<?php

namespace LightMoon;

use FastRoute\Dispatcher;
use FastRoute\Dispatcher\GroupCountBased;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;
use Pimple\Container;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Factory;
use React\Http\Response;
use React\Socket\Server;

class App
{

    /**
     * @var Container
     */
    private $container;

    /**
     * @var \React\EventLoop\ExtEventLoop|\React\EventLoop\LibEventLoop|\React\EventLoop\LibEvLoop|\React\EventLoop\StreamSelectLoop
     */
    private $httpServerLoop;

    /**
     * @var Server
     */
    private $socket;

    /**
     * @var \React\Http\Server
     */
    private $httpServer;

    private $port;

    /**
     * App constructor.
     * @param array $setting
     */
    public function __construct(array $setting)
    {
        $this->container = new Container();
        $this->container['setting'] = $setting;

        $this->port = $this->container['setting']['listen'];

        $this->container['router.collector'] = function () {
            /** @var RouteCollector $routeCollector */
            return new RouteCollector(
                new Std(), new \FastRoute\DataGenerator\GroupCountBased()
            );
        };

        $this->container['router.dispatch'] = function ($c) {
            return new GroupCountBased($c['router.collector']->getData());
        };

        $this->httpServerLoop = Factory::create();
        $this->socket = new Server($this->port, $this->httpServerLoop);

        $this->httpServer = new \React\Http\Server(function (ServerRequestInterface $request) {

            $httpMethod = $request->getMethod();
            $uri = $request->getUri();

            $routeInfo = $this->container['router.dispatch']->dispatch($httpMethod, $uri->getPath());

            if ($routeInfo[0] === Dispatcher::FOUND) {

                $response = new Response();
                if(is_callable($routeInfo[1])) {
                    $res = call_user_func_array($routeInfo[1], [$request, $response]);

                    return $res;
                } else {

                    list($class, $method) = explode(':', $routeInfo[1]);

                    $controller = new $class($this->container);

                    return $controller->$method($request, $response);
                }

            } elseif ($routeInfo[0] === Dispatcher::METHOD_NOT_ALLOWED) {

                return new Response(405, array('Content-Type' => 'text/plain'), 'Method Not Allowed');
            } elseif ($routeInfo[0] === Dispatcher::NOT_FOUND) {
                return new Response(404, ['Content-Type' => 'text/plain'], 'Not Found');
            }

        });
    }

    public function get($method, $uri, $handler) {
        $this->container['router.collector']->addRoute($method, $uri, $handler);
    }

    public function post($method, $uri, $handler) {
        $this->container['router.collector']->addRoute($method, $uri, $handler);
    }

    public function run()
    {
        $this->httpServer->listen($this->socket);

        echo "Server running at http://127.0.0.1:{$this->port}\n";

        $this->httpServerLoop->run();
    }

}
