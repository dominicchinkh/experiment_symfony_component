<?php

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

return function () {
    $request = Request::createFromGlobals();

    // Define routes
    $routes = new RouteCollection();

    $routes->add(
        'home', 
        new Route(
            '/', 
            [
                '_controller' => function () {
                    return new Response('Welcome home!');
                }
            ]
        )
    );

    $routes->add(
        'hello', 
        new Route(
            '/hello/{name}', 
            [
                '_controller' => function (string $name) {
                    return new Response("Hello, $name!");
                }
            ]
        )
    );

    // Match the request
    $context = new RequestContext();
    $context->fromRequest($request);
    $matcher = new UrlMatcher($routes, $context);

    try {
        $parameters = $matcher->match($request->getPathInfo());
        $controller = $parameters['_controller'];
        unset($parameters['_controller'], $parameters['_route']);
        $response = $controller(...array_values($parameters));
        
    } catch (ResourceNotFoundException $e) {
        $response = new Response('Not Found', 404);
    }

    $response->send();
};
