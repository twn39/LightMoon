<?php


namespace LightMoon\Middleware;


class JsonResponseMiddleware
{

    public function __invoke($request, $response)
    {
        $response->header('Content-Type', "Application/json;Charset=utf-8");
        return $response;
    }
}