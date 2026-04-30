<?php

namespace Dominic\ExperimentSymfonyComponent\Security;

use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class InMemoryUserProvider implements UserProviderInterface
{
    /** @var array<string, UserInterface> */
    private array $users = [];

    public function addUser(UserInterface $user): void
    {
        $this->users[$user->getUserIdentifier()] = $user;
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        if (!isset($this->users[$identifier])) {
            throw new UserNotFoundException(sprintf('User "%s" not found.', $identifier));
        }

        return $this->users[$identifier];
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return $class === User::class;
    }
}
