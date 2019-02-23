<?php

use LightMoon\Application;
use Zend\Config\Config;

require __DIR__.'/../vendor/autoload.php';

$config = [
    'server' => [
        'host' => '0.0.0.0',
        'port' => '8060',
    ]
];

class HomeController
{
    public function index($request, $response, $params)
    {
        $response->write("hello swoole");

        return $response;
    }

    public function site($request, $response, $params)
    {
        $response->write(json_encode([
            'hello' => 'world',
        ]));
        return $response;
    }
}

class HomeControllerProvider implements \Pimple\ServiceProviderInterface
{

    public function register(\Pimple\Container $pimple)
    {
        $pimple[HomeController::class] = new HomeController();
    }
}

$app = new Application(new Config($config));
$app->register(new HomeControllerProvider());
$app->middleware(function ($request, $response) {
    $response->header('Content-type', "Application/json;charset=utf-8");
    return $response;
}, $priority = 10);

$app->get('home', '/', HomeController::class.'::site');

$app->run();

