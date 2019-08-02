<?php
/**
 * 使用 PDO 或者 Mysqli 确保开启 Swoole\Runtime::enableCoroutine()
 */

namespace LightMoon\Providers;

use Pimple\Container;
use Zend\Config\Config;
use LightMoon\Coroutine\DBPool;
use Pimple\ServiceProviderInterface;
use Illuminate\Database\Connectors\ConnectionFactory;

class DatabaseProvider implements ServiceProviderInterface
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
        $container[DBPool::class] = function ($c) {
            $config = $c[Config::class]['DB']->toArray();
            $factory = new ConnectionFactory(new \Illuminate\Container\Container());

            $size = $config['pool']['size'] ?? 5;
            return new DBPool($factory, $config, $size);
        };
    }
}