<?php

namespace LightMoon\Middleware;

use Fig\Http\Message\StatusCodeInterface;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

class RequestHandlerProvider implements ServiceProviderInterface
{
    /**
     * Registers services on the given container.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Container $container
     */
    public function register(Container $container)
    {
        $middleware = function ($request, $response) use ($container) {
            $context = new RequestContext(
                '/',
                $request->server['request_method'],
                $request->header['host'],
                'http',
                $request->server['server_port'],
                443,
                '/',
                ''
            );

            $matcher = new UrlMatcher($container[RouteCollection::class], $context);
            try {
                $params = $matcher->match($request->server['request_uri']);
                $controller = $container[$params['_controller']];
                $action = $params['_action'];
                $response = $controller->$action($request, $response, $params);
            } catch (ResourceNotFoundException $exception) {
                $response->status(StatusCodeInterface::STATUS_NOT_FOUND);
                $response->write('Resource not found');
            } catch (MethodNotAllowedException $exception) {
                $response->status(StatusCodeInterface::STATUS_METHOD_NOT_ALLOWED);
                $response->write('Method not allowed');
            }

            return $response;
        };
        $container[PriorityMiddleware::class]->insert($middleware, 0);
    }
}
