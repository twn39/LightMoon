<?php
/**
 * 使用 PDO 或者 Mysqli 确保开启 Swoole\Runtime::enableCoroutine()
 */

namespace LightMoon\Coroutine;

use Swoole\Coroutine as Co;
use Illuminate\Database\Connection;
use Illuminate\Database\Connectors\ConnectionFactory;

class DBPool
{
    /**
     * @var Co\Channel
     */
    private $pool;

    /**
     * @var int
     */
    private $size;

    /**
     * @var ConnectionFactory
     */
    private $factory;

    /**
     * @var \Closure
     */
    private $reconnector;

    /**
     * @var array
     */
    private $config;

    /**
     * DBPool constructor.
     * @param ConnectionFactory $factory
     * @param array $config
     * @param $size
     */
    public function __construct(ConnectionFactory $factory, array $config, int $size)
    {
        $this->pool = new Co\Channel($size);
        $this->factory = $factory;
        $this->size = $size;
        $this->config = $config;

        $this->reconnector = function (Connection $connection) {
            $conn = $this->factory->make($this->config);
            $connection->setPdo($conn->getPdo())
                ->setReadPdo($conn->getReadPdo());
        };

        for($i = 0; $i < $size; $i++) {
            $connection = $this->factory->make($config);
            $connection->setReconnector($this->reconnector);
            $this->push($connection);
        }
    }

    /**
     * @return array
     */
    public function stats()
    {
        return $this->pool->stats();
    }

    /**
     * @return mixed
     */
    public function length()
    {
        return $this->pool->length();
    }

    /**
     * @return bool
     */
    public function empty()
    {
        return $this->pool->isEmpty();
    }

    /**
     * @return bool
     */
    public function full()
    {
        return $this->pool->isFull();
    }

    /**
     * @return mixed
     */
    public function capacity()
    {
        return $this->pool->capacity;
    }
    /**
     * @param Connection $connection
     */
    public function push(Connection $connection)
    {
        $this->pool->push($connection);
    }

    public function close()
    {
        $this->pool->close();
        $this->pool = null;
    }

    /**
     * @param Connection $connection
     */
    public function release(Connection $connection)
    {
        $this->push($connection);
    }

    /**
     * @return mixed
     */
    public function get()
    {
        return $this->pool->pop();
    }

}