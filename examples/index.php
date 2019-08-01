<?php

use Pimple\Container;
use Zend\Config\Config;
use LightMoon\Application;
use Pimple\ServiceProviderInterface;
use LightMoon\Middleware\JsonResponseMiddleware;

require __DIR__.'/../vendor/autoload.php';

$config = [
    'server' => [
        'host' => '0.0.0.0',
        'port' => '8060',
        'worker_num' => 2,
    ]
];

class HomeController
{
    public function site($request, $response, $params)
    {
        $response->write(json_encode([
            'title' => 'hello swoole !',
        ]));
        return $response;
    }

    public function api($request, $response, $attr) {
        $response->write(json_encode([
            'version' => $attr['version'],
        ]));

        return $response;
    }
}

class HomeControllerProvider implements ServiceProviderInterface
{
    public function register(Container $container)
    {
        $container[HomeController::class] = new HomeController();
    }
}

$app = new Application(new Config($config));
$app->register(new HomeControllerProvider());
$app->middleware(new JsonResponseMiddleware(), $priority = 10);

$app->get('home', '/', HomeController::class.'@site');
$app->get('api', '/api/{version}', HomeController::class.'@api', [
    'version' => 'v[1-9]+'
]);

$app->run();
