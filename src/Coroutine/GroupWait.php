<?php
/**
 * Created by PhpStorm.
 * User: weinan
 * Date: 2019/2/23
 * Time: 21:36
 *
 * 首先确保所执行的函数体里面没有阻塞的函数，例如PDO，file_get_contents等等，
 * 如果有这些函数，请先执行： Swoole\Runtime::enableCoroutine();
 */

use Swoole\Coroutine as Co;

class GroupWait
{
    private $channel;

    private $coList;

    private $len;

    public function __construct(array $coList)
    {
        $this->coList = $coList;
        $this->len = count($coList);
        $this->channel = new Co\channel($this->len);
    }

    public function wait()
    {
        foreach ($this->coList as $key => $co) {
            go(function () use ($key, $co) {
                $data = $co();
                $this->channel->push([$key, $data]);
            });
        }

        $result = [];
        for($i = 1; $i <= $this->len; $i++) {
            list($k, $v) = $this->channel->pop();
            $result[$k] = $v;
        }

        return $result;
    }

    public function __destruct()
    {
        $this->channel->close();
    }

}
