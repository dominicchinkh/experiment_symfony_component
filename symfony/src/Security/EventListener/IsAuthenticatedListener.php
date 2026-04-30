<?php

namespace Dominic\ExperimentSymfonyComponent\Security\EventListener;

use Dominic\ExperimentSymfonyComponent\Security\Attribute\IsAuthenticated;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class IsAuthenticatedListener implements EventSubscriberInterface
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private string $loginUrl = '/saml/login',
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER_ARGUMENTS => ['onControllerArguments', 0],
        ];
    }

    public function onControllerArguments(ControllerArgumentsEvent $event): void
    {
        $attributes = $event->getAttributes();

        if (!isset($attributes[IsAuthenticated::class])) {
            return;
        }

        $token = $this->tokenStorage->getToken();

        if (null === $token || !$token->getUser()) {
            $event->setController(fn () => new RedirectResponse($this->loginUrl));
        }
    }
}
