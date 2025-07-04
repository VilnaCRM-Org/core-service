<?php

declare(strict_types=1);

namespace App\Tests\Integration\Negative\Kernel;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

final class NegativeKernel extends BaseKernel
{
    use MicroKernelTrait;

    public const CONFIG_EXTS = '.{php,xml,yaml,yml}';
    private const CONFIG_DOCTRINE_YAML = '/config/doctrine.yaml';

    /**
     * @var array<string>
     *
     * @psalm-suppress UnusedProperty
     */
    private array $extraConfigs;
}
