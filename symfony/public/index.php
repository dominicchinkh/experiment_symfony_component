<?php

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

use Dominic\ExperimentSymfonyComponent\Controller\HomeController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
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
            [ '_controller' => HomeController::class.'::index' ]
        )
    );

    $routes->add(
        'hello', 
        new Route(
            '/hello/{name}', 
            [ '_controller' => HomeController::class.'::hello' ]
        )
    );

    // Match the request
    $context = new RequestContext();
    $context->fromRequest($request);
    $matcher = new UrlMatcher($routes, $context);

    try {
        $parameters = $matcher->match($request->getPathInfo());
        $request->attributes->add($parameters);

        $controllerResolver = new ControllerResolver();
        $argumentResolver   = new ArgumentResolver();

        $controller = $controllerResolver->getController($request);
        $arguments  = $argumentResolver->getArguments($request, $controller);

        $response = call_user_func_array($controller, $arguments);

    } catch (ResourceNotFoundException $e) {
        $response = new Response('Not Found', 404);
    }

    $response->send();
};
