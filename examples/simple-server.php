<?php
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

require '../vendor/autoload.php';

$app = new LightMoon\App([
    'host' => '127.0.0.1',
    'listen' => '8080',
    'server' => [
        'worker_num' => 4,
    ]
]);

$app->get('/', function (RequestInterface $request, ResponseInterface $response) {

    return $response->getBody()->write("hello world!");
});

$app->run();
