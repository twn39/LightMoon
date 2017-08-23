<?php
require 'vendor/autoload.php';

class UserController
{
    private $container;

    public function __construct(\Pimple\Container $container)
    {
        $this->container = $container;
    }

    public function show(\RingCentral\Psr7\Request $request, \React\Http\Response $response)
    {
        $response->withHeader('Content-type', 'text/plain');
        $response->getBody()->write("hello react php");

        return $response;
    }
}
$app = new LightMoon\App([
    'listen' => '8080',
]);
$app->get('GET', '/user/{id}', 'UserController:show');

$app->run();

