<?php

namespace Dominic\ExperimentSymfonyComponent\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class HomeController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        return new Response('Welcome home!');
    }

    #[Route('/hello/{name}', name: 'hello')]
    public function hello(string $name): Response
    {
        return new Response("Hello, $name!");
    }

    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(TokenStorageInterface $tokenStorage): Response
    {
        $token = $tokenStorage->getToken();

        if (null === $token || !$token->getUser()) {
            return new JsonResponse(['error' => 'Authentication required'], 401);
        }

        $user = $token->getUser();

        return new JsonResponse([
            'message' => 'Welcome to the dashboard!',
            'user' => $user->getUserIdentifier(),
            'roles' => $user->getRoles(),
        ]);
    }
}
