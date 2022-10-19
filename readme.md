
LightMoon是一个基于 Swoole 的微型框架，灵感来自于 Slimphp。

### 设计理念

简单至上，越少的代码意味着越少的 bug，此框架核心代码加上注释不足500行，路由采用 Symfony routing，依赖管理使用 pimple。
不同的项目有不同的需求，有人会使用 MVC 全功能框架，例如 Laravel 来开发整个业务，也有人会使用 Slimphp 这种微型框架来开发 api。
通过pimple很容易将 lightmoon 扩展为全功能框架，也可以仅仅使用核心功能来开发api。

### 优势

LightMoon 基于 Swoole，因此在性能方面有很大的优势，API借鉴于 Slimphp，熟悉 Slimphp 的用户能够很快上手，
灵活容易定制，甚至可以 fork 本项目修改核心代码来满足业务需求，在能够极大地提升性能的同时，可以利用 php 完整，成熟的生态。

### 缺点

LightMoon 是基于 swoole，因此需要对 Swoole 有基本的了解。

### 教程

#### 安装

```
composer require lightmoon/lightmoon
```

#### 使用

**简单示例**

```php
<?php

use Pimple\Container;
use Zend\Config\Config;
use LightMoon\Application;
use Pimple\ServiceProviderInterface;
use LightMoon\Middleware\JsonResponseMiddleware;

require __DIR__.'/vendor/autoload.php';

$config = [
    'server' => [
        'host' => '0.0.0.0',
        'port' => '8060',
        'worker_num' => 2,
    ]
];

class HomeController
{
    public function site($request, $response)
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
$app->get('home', '/', 'HomeController@index')
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

添加数据库：

LightMoon 提供了 Eloquent ORM 封装，默认并没有启用，如需启用，可添加配置：

```php
$config = [
    'server' => [
        'host' => '0.0.0.0',
        'port' => '8060',
        'worker_num' => 2,
    ],
    'DB' => [
        'driver' => 'sqlite',
        'database' => __DIR__ . '/DB.sqlite',
        'prefix' => '',
        'foreign_key_constraints' => true,
        'pool' => [
            'size' => 3,
        ]
    ],
```

注入数据库服务：

```php 
$app->register(new DatabaseProvider());
```

> 注：Eloquent ORM 使用的是 PDO 和 Mysqli， 所以需要开启：`Swoole\Runtime::enableCoroutine()`。
