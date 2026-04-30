<?php

namespace Dominic\ExperimentSymfonyComponent\Security;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticatorManager;
use Symfony\Component\Security\Http\Firewall;
use Symfony\Component\Security\Http\Firewall\AuthenticatorManagerListener;
use Symfony\Component\Security\Http\Firewall\ContextListener;
use Symfony\Component\Security\Http\FirewallMapInterface;
use Symfony\Component\Yaml\Yaml;

class FirewallFactory
{
    public static function create(
        string $configPath,
        TokenStorageInterface $tokenStorage,
        UserProviderInterface $userProvider,
        AuthenticatorManager $authenticatorManager,
        EventDispatcherInterface $dispatcher,
    ): Firewall {
        $config = Yaml::parseFile($configPath)['security']['firewall'];

        $firewallName = $config['name'] ?? 'main';
        $contextKey = $config['context'] ?? $firewallName;

        $authenticatorManagerListener = new AuthenticatorManagerListener($authenticatorManager);
        $contextListener = new ContextListener(
            $tokenStorage, [$userProvider], $contextKey, null, $dispatcher
        );

        $firewallMap = new class($contextListener, $authenticatorManagerListener) implements FirewallMapInterface {
            public function __construct(
                private ContextListener $contextListener,
                private AuthenticatorManagerListener $authListener,
            ) {}

            public function getListeners(Request $request): array
            {
                return [[$this->contextListener, $this->authListener], null, null];
            }
        };

        return new Firewall($firewallMap, $dispatcher);
    }
}
