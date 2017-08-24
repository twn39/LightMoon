<?php
require 'vendor/autoload.php';

class UserController
{
    private $container;

    public function __construct(\Pimple\Container $container)
    {
        $this->container = $container;
    }

    public function show($request, $response, $args)
    {
        $response->end("hello swoole");
    }
}
$app = new LightMoon\App([
    'host' => '127.0.0.1',
    'listen' => '8080',
    'server' => [
        'worker_num' => 4,
    ]
]);
$app->get('/', 'UserController:show');

$app->run();

