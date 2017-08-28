<?php
require 'vendor/autoload.php';

$app = new LightMoon\App([
    'host' => '127.0.0.1',
    'listen' => '8080',
    'server' => [
        'worker_num' => 4,
        'pid_file' => __DIR__.'/server.pid',
    ]
]);
$app->group('/users', function (\LightMoon\App $app) {
   $app->get('/{id}', UserController::class.':show',
       function (\LightMoon\Http\Request $request, \LightMoon\Http\Response $response, $next) {
            $response = $next($request, $response);
            return $response;
       });
});

$app->on('workerstart', function () {
    require __DIR__ . '/UserController.php';

});

$app->run();

