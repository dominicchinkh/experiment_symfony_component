<?php

namespace Dominic\ExperimentSymfonyComponent\Controller;

use Symfony\Component\HttpKernel\Controller\ControllerResolver as BaseControllerResolver;

class ControllerResolver extends BaseControllerResolver
{
    /** @var array<class-string, object> */
    private array $controllers = [];

    public function registerController(string $class, object $instance): void
    {
        $this->controllers[$class] = $instance;
    }

    protected function instantiateController(string $class): object
    {
        if (isset($this->controllers[$class])) {
            return $this->controllers[$class];
        }

        return parent::instantiateController($class);
    }
}
