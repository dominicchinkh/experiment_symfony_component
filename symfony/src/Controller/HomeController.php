<?php

namespace Dominic\ExperimentSymfonyComponent\Controller;

use Symfony\Component\HttpFoundation\Response;

class HomeController
{
    public function index(): Response
    {
        return new Response('Welcome home!');
    }

    public function hello(string $name): Response
    {
        return new Response("Hello, $name!");
    }
}
