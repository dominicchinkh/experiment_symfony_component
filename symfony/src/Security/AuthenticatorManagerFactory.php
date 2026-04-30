<?php

namespace Dominic\ExperimentSymfonyComponent\Security;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticatorManager;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;
use Symfony\Component\Security\Http\EventListener\UserProviderListener;
use Symfony\Component\Yaml\Yaml;

class AuthenticatorManagerFactory
{
    public static function create(
        string $configPath,
        array $samlSettings,
        TokenStorageInterface $tokenStorage,
        EventDispatcherInterface $dispatcher,
    ): array {
        $config = Yaml::parseFile($configPath)['security'];

        // --- User Provider ---
        $userProvider = new InMemoryUserProvider();
        foreach ($config['providers']['in_memory']['users'] as $identifier => $userData) {
            $roles = $userData['roles'] ?? ['ROLE_USER'];
            $userProvider->addUser(new User($identifier, $roles));
        }

        // --- Authenticators ---
        $authenticators = [];

        if (isset($config['authenticators']['api_key'])) {
            $keys = $config['authenticators']['api_key']['keys'] ?? [];
            $authenticators[] = new ApiKeyAuthenticator($keys);
        }

        if (array_key_exists('saml', $config['authenticators'])) {
            $authenticators[] = new SamlAuthenticator($samlSettings);
        }

        // --- Register UserProviderListener ---
        $userProviderListener = new UserProviderListener($userProvider);
        $dispatcher->addListener(CheckPassportEvent::class, [$userProviderListener, 'checkPassport']);

        // --- AuthenticatorManager ---
        $firewallName = $config['firewall']['name'] ?? 'main';

        $authenticatorManager = new AuthenticatorManager(
            $authenticators,
            $tokenStorage,
            $dispatcher,
            $firewallName,
        );

        return [
            'authenticatorManager' => $authenticatorManager,
            'userProvider' => $userProvider,
            'firewallName' => $firewallName,
        ];
    }
}
