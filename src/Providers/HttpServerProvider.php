<?php

namespace LightMoon\Providers;

use Pimple\Container;
use swoole_http_server;
use Zend\Config\Config;
use Pimple\ServiceProviderInterface;

class HttpServerProvider implements ServiceProviderInterface
{

    /**
     * Registers services on the given container.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Container $container
     */
    public function register(Container $container)
    {
        $config = $container[Config::class]['server'];
        $container[swoole_http_server::class] = new swoole_http_server($config['host'], $config['port']);
    }
}
