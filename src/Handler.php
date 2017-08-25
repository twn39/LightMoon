<?php

namespace LightMoon;

use Pimple\Container;

class Handler
{
    public $callback;

    /**
     * @var Container
     */
    public $container;

    /**
     * Handler constructor.
     * @param Container $container
     * @param $callback
     */
    public function __construct(Container $container, $callback)
    {
        $this->container = $container;
        $this->callback = $callback;
    }

    /**
     * @param $callback
     * @return array
     */
    public function resolveCallBack($callback)
    {
        if (!is_callable($callback)) {
            list($class, $method) = explode(':', $callback);
            $controller = new $class($this->container);
            $callback = [$controller, $method];
        }

        return $callback;
    }

    /**
     * @param $request
     * @param $response
     * @return mixed
     */
    public function __invoke($request, $response)
    {
        $callback = $this->resolveCallBack($this->callback);

        $response = call_user_func_array($callback, [$request, $response]);

        return $response;
    }
}
