<?php

namespace Dominic\ExperimentSymfonyComponent\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AdminController
{
    #[Route('/admin/dashboard', name: 'dashboard')]
    public function dashboard(TokenStorageInterface $tokenStorage): Response
    {
        $user = $tokenStorage->getToken()->getUser();

        return new JsonResponse([
            'message' => 'Welcome to the dashboard!',
            'user' => $user->getUserIdentifier(),
            'roles' => $user->getRoles(),
        ]);
    }
}
