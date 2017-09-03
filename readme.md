
LightMoon是一个基于swoole的微型框架，灵感来自于Slimphp。

### 设计理念

简单至上，越少的代码意味着越少的bug，此框架核心代码加上注释不足1000行，路由采用fastroute，依赖管理使用pimple，兼容psr7。
不同的项目有不同的需求，有人会使用MVC全功能框架，例如Laravel来开发整个业务，也有人会使用Slimphp这种微型框架来开发api。
通过pimple很容易将lightmoon扩展为全功能框架，也可以仅仅使用核心功能来开发api。

### 优势

LightMoon基于Swoole，因此在性能方面有很大的优势，API借鉴于Slimphp，熟悉Slimphp的用户能够很快上手，
灵活容易定制，甚至可以fork本项目修改核心代码来满足业务需求，在能够极大地提升性能的同时，可以利用php完整，成熟的生态。

### 缺点

Lightoon是基于swoole，因此需要对swoole有基本的了解。

### 教程

#### 安装

```
composer require lightmoon/lightmoon
```

#### 使用

**简单示例**

```php
<?php

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

require 'vendor/autoload.php';

$app = new LightMoon\App([
    'host' => '127.0.0.1',
    'listen' => '8080',
    'server' => [
        'worker_num' => 4,
    ]
]);

$app->get('/', function (RequestInterface $request, ResponseInterface $response) {

    $response->getBody()->write("hello world!");
    return $response;
});

$app->run();
```


**使用class**


```php
<?php
use LightMoon\Http\Request;
use LightMoon\Http\Response;

require 'vendor/autoload.php';

$app = new LightMoon\App([
    'host' => '127.0.0.1',
    'listen' => '8080',
    'server' => [
        'worker_num' => 4,
    ]
]);

class HomeController
{
    private $container;

    public function __construct(\Pimple\Container $container)
    {
        $this->container = $container;
    }

    public function show(Request $request, Response $response)
    {
        $response->getBody()->write("hello world!");
        return $response;
    }
}

$app->get('/', 'HomeController:show');

$app->run();
```

**middleware**

```php
<?php

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

require 'vendor/autoload.php';

$app = new LightMoon\App([
    'host' => '127.0.0.1',
    'listen' => '8080',
    'server' => [
        'worker_num' => 4,
    ]
]);

$app->get('/', function (RequestInterface $request, ResponseInterface $response) {

    $response->getBody()->write("hello world!");
    return $response;
}, function (RequestInterface $request, ResponseInterface $response, $next) {
    $response->getBody()->write('<p>before</p>');
    $response = $next($request, $response);
    $response->getBody()->write('<p>after</p>');

    return $response;
});

$app->run();
```

**swoole event**

```php
<?php

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

require 'vendor/autoload.php';

$app = new LightMoon\App([
    'host' => '127.0.0.1',
    'listen' => '8080',
    'server' => [
        'worker_num' => 4,
    ]
]);

$app->on('workerstart', function () {
    echo "worker started\n";
});

$app->on('start', function () {
    echo "start....\n";
});

$app->get('/', function (RequestInterface $request, ResponseInterface $response) {

    $response->getBody()->write("hello world!");
    return $response;
});

$app->run();
```