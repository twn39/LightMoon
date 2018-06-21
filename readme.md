
LightMoon是一个基于swoole的微型框架，灵感来自于Slimphp。

### 设计理念

简单至上，越少的代码意味着越少的bug，此框架核心代码加上注释不足500行，路由采用Symfony routing，依赖管理使用pimple。
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

use LightMoon\Application;
use Zend\Config\Config;

require "vendor/autoload.php";

$config = [
    'server' => [
        'host' => 'localhost',
        'port' => '9501',
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

$app->get('home', '/', HomeController::class.'::site');

$app->run();
```

**middleware**

```php
<?php

$app->middleware(function ($request, $response) {
    $response->header('Content-type', "Application/json");
    return $response;
}, $priority = 10);

```

**swoole event**

```php
<?php

$app->on('workerstart', function () {
    echo "worker started\n";
});

$app->on('start', function () {
    echo "start....\n";
});

```

**路由**


路由支持: `GET`, `POST`, `PUT`, `DELETE`, `PATCH`, `HEAD`,

```php
$app->get('home', '/', 'HomeController::index')
```

**注册组件**

创建组建：

```php
use Pimple\Container;

class FooProvider implements Pimple\ServiceProviderInterface
{
    public function register(Container $pimple)
    {
        // register some services and parameters
        // on $pimple
    }
}
```

注册：

```php
$app->register(new FooProvider());
```