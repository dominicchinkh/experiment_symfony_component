<?php

namespace Dominic\ExperimentSymfonyComponent\Security\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Yaml\Yaml;

class AccessControlListener implements EventSubscriberInterface
{
    private array $rules;

    public function __construct(
        private TokenStorageInterface $tokenStorage,
        string $configPath,
        private string $loginUrl = '/saml/login',
    ) {
        $config = Yaml::parseFile($configPath)['security'];
        $this->rules = $config['access_control'] ?? [];
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Run after firewall (priority 8) has authenticated the request
            KernelEvents::REQUEST => ['onKernelRequest', 6],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        foreach ($this->rules as $rule) {
            $pattern = $rule['path'] ?? null;
            if (!$pattern) {
                continue;
            }

            if (!preg_match('#'.$pattern.'#', $path)) {
                continue;
            }

            $requiredRoles = (array) ($rule['roles'] ?? []);
            $token = $this->tokenStorage->getToken();

            // Not authenticated at all — redirect to login
            if (null === $token || !$token->getUser()) {
                $event->setResponse(new RedirectResponse($this->loginUrl));
                return;
            }

            // IS_AUTHENTICATED means any logged-in user is allowed
            if ($requiredRoles === ['IS_AUTHENTICATED']) {
                return;
            }

            // Check roles
            if (!empty($requiredRoles)) {
                $userRoles = $token->getUser()->getRoles();
                $hasRole = !empty(array_intersect($requiredRoles, $userRoles));
                if (!$hasRole) {
                    $event->setResponse(new RedirectResponse($this->loginUrl));
                    return;
                }
            }

            // First matching rule wins
            return;
        }
    }
}
