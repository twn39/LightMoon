<?php

namespace LightMoon;

use Pimple\Container;

class Handler
{
    public $callback;

    public $container;

    public function __construct(Container $container, $callback)
    {
        $this->container = $container;
        $this->callback = $callback;
    }

    public function resolveCallBack($callback)
    {
        if (!is_callable($callback)) {
            list($class, $method) = explode(':', $callback);
            $controller = new $class($this->container);
            return [$controller, $method];
        } else {
            return $callback;
        }
    }

    public function __invoke($request, $response)
    {
        $callback = $this->resolveCallBack($this->callback);

        $response = call_user_func_array($callback, [$request, $response]);

        return $response;
    }
}
