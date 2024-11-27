<?php

namespace App\Tests;

use App\Shared\AppKernel;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class ConfigurableContainerFactory
{
    public function create(array $configs): ContainerInterface
    {
        $kernel = new AppKernel('dev', true, $configs);
        $kernel->boot();

        return $kernel->getContainer();
    }

}