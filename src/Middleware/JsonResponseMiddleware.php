<?php


namespace LightMoon\Middleware;


class JsonResponseMiddleware
{

    public function __invoke($request, $response)
    {
        $response->header('Content-type', "Application/json;charset=utf-8");
        return $response;
    }
}