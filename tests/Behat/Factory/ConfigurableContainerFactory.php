<?php

declare(strict_types=1);

namespace App\Tests\Behat\Factory;

use App\Tests\Behat\Kernel\AppKernel;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class ConfigurableContainerFactory
{
    public function create(array $configs): ContainerInterface
    {
        $kernel = new AppKernel('test', true, $configs);
        $kernel->boot();

        return $kernel->getContainer();
    }
}
