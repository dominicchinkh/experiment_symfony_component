<?php

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

use Dominic\ExperimentSymfonyComponent\Controller\ControllerResolver;
use Dominic\ExperimentSymfonyComponent\Controller\HomeController;
use Dominic\ExperimentSymfonyComponent\Controller\SamlController;
use Dominic\ExperimentSymfonyComponent\Routing\AttributeRouteLoader;
use Dominic\ExperimentSymfonyComponent\Security\AuthenticatorManagerFactory;
use Dominic\ExperimentSymfonyComponent\Security\FirewallFactory;
use Dominic\ExperimentSymfonyComponent\Security\TokenStorageValueResolver;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver\DefaultValueResolver;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver\RequestAttributeValueResolver;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver\RequestValueResolver;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver\VariadicValueResolver;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

return function () {

    // --- SAML Settings ---
    $samlSettings = require dirname(__DIR__).'/config/saml_settings.php';

    // --- Routing (loaded from #[Route] attributes) ---
    $routeLoader = new AttributeRouteLoader();
    $routes = new RouteCollection();
    $routes->addCollection($routeLoader->load(HomeController::class));
    $routes->addCollection($routeLoader->load(SamlController::class));

    $context = new RequestContext();
    $matcher = new UrlMatcher($routes, $context);

    // --- Security (configured via config/security.yaml) ---
    $tokenStorage = new TokenStorage();
    $dispatcher   = new EventDispatcher();
    $requestStack = new RequestStack();

    $security = AuthenticatorManagerFactory::create(
        dirname(__DIR__).'/config/security.yaml',
        $samlSettings,
        $tokenStorage,
        $dispatcher,
    );

    // --- Firewall (configured via config/security.yaml) ---
    $firewall = FirewallFactory::create(
        dirname(__DIR__).'/config/security.yaml',
        $tokenStorage,
        $security['userProvider'],
        $security['authenticatorManager'],
        $dispatcher,
    );
    $dispatcher->addSubscriber($firewall);

    // Register the RouterListener (resolves routes on kernel.request)
    $routerListener = new RouterListener($matcher, $requestStack, $context);
    $dispatcher->addSubscriber($routerListener);

    // --- HttpKernel ---
    $controllerResolver = new ControllerResolver();
    $controllerResolver->registerController(SamlController::class, new SamlController($samlSettings));

    $argumentResolver = new ArgumentResolver(null, [
        new TokenStorageValueResolver($tokenStorage),
        new RequestAttributeValueResolver(),
        new RequestValueResolver(),
        new DefaultValueResolver(),
        new VariadicValueResolver(),
    ]);

    $kernel = new HttpKernel($dispatcher, $controllerResolver, $requestStack, $argumentResolver);

    $request = Request::createFromGlobals();
    $request->setSession(new Session());
    $response = $kernel->handle($request);
    $response->send();
    $kernel->terminate($request, $response);
};
