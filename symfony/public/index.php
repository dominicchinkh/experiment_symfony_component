<?php

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

use Dominic\ExperimentSymfonyComponent\Controller\HomeController;
use Dominic\ExperimentSymfonyComponent\Security\ApiKeyAuthenticator;
use Dominic\ExperimentSymfonyComponent\Security\InMemoryUserProvider;
use Dominic\ExperimentSymfonyComponent\Security\TokenStorageValueResolver;
use Dominic\ExperimentSymfonyComponent\Security\User;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver\DefaultValueResolver;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver\RequestAttributeValueResolver;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver\RequestValueResolver;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver\VariadicValueResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Http\Authentication\AuthenticatorManager;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;
use Symfony\Component\Security\Http\EventListener\UserProviderListener;
use Symfony\Component\Security\Http\Firewall;
use Symfony\Component\Security\Http\Firewall\AuthenticatorManagerListener;
use Symfony\Component\Security\Http\FirewallMapInterface;

return function () {

    // --- Routing ---
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

    $routes->add(
        'dashboard', 
        new Route(
            '/dashboard', 
            [ '_controller' => HomeController::class.'::dashboard' ]
        )
    );

    $context = new RequestContext();
    $matcher = new UrlMatcher($routes, $context);

    // --- Security ---
    $tokenStorage = new TokenStorage();

    // Users (in-memory for demo)
    $userProvider = new InMemoryUserProvider();
    $userProvider->addUser(new User('admin', ['ROLE_ADMIN']));
    $userProvider->addUser(new User('user', ['ROLE_USER']));

    // API Key authenticator: key => user identifier
    $authenticator = new ApiKeyAuthenticator([
        'secret-admin-key' => 'admin',
        'secret-user-key' => 'user',
    ]);

    // Event Dispatcher
    $dispatcher   = new EventDispatcher();
    $requestStack = new RequestStack();

    // Register UserProviderListener to resolve users from UserBadge
    $userProviderListener = new UserProviderListener($userProvider);
    $dispatcher->addListener(CheckPassportEvent::class, [$userProviderListener, 'checkPassport']);

    // AuthenticatorManager handles the authentication flow
    $authenticatorManager = new AuthenticatorManager(
        [$authenticator],
        $tokenStorage,
        $dispatcher,
        'main',
    );

    // AuthenticatorManagerListener is the firewall listener
    $authenticatorManagerListener = new AuthenticatorManagerListener($authenticatorManager);

    // FirewallMap: returns listeners for a given request
    $firewallMap = new class($authenticatorManagerListener) implements FirewallMapInterface {
        public function __construct(private AuthenticatorManagerListener $listener) {}

        public function getListeners(Request $request): array
        {
            return [[$this->listener], null, null];
        }
    };

    // Register the Firewall (listens to kernel.request)
    $firewall = new Firewall($firewallMap, $dispatcher);
    $dispatcher->addSubscriber($firewall);

    // Register the RouterListener (resolves routes on kernel.request)
    $routerListener = new RouterListener($matcher, $requestStack, $context);
    $dispatcher->addSubscriber($routerListener);

    // --- HttpKernel ---
    $controllerResolver = new ControllerResolver();
    
    $argumentResolver = new ArgumentResolver(null, [
        new TokenStorageValueResolver($tokenStorage),
        new RequestAttributeValueResolver(),
        new RequestValueResolver(),
        new DefaultValueResolver(),
        new VariadicValueResolver(),
    ]);

    $kernel = new HttpKernel($dispatcher, $controllerResolver, $requestStack, $argumentResolver);

    $request = Request::createFromGlobals();
    $response = $kernel->handle($request);
    $response->send();
    $kernel->terminate($request, $response);
};
