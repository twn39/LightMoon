<?php

namespace LightMoon\Providers;

use Pimple\Container;
use Swoole\Http\Server;
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
        $container[Server::class] = function ($c) {
            $config = $c[Config::class]['server'];
            return new Server($config['host'], $config['port']);
        };
    }
}
