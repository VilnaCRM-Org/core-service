<?php

declare(strict_types=1);

namespace App\Tests\Integration\Negative\Kernel;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

final class NegativeKernel extends BaseKernel
{
    use MicroKernelTrait;

    public const CONFIG_EXTS = '.{php,xml,yaml,yml}';
    private const CONFIG_DOCTRINE_YAML = '/config/doctrine.yaml';

    /**
     * @var iterable<string>
     */
    private array $extraConfigs;

    /**
     * @psalm-api
     */
    public function __construct(
        string $environment,
        bool $debug,
    ) {
        parent::__construct($environment, $debug);
        $this->extraConfigs = [__DIR__ . self::CONFIG_DOCTRINE_YAML];
    }

    /**
     * @psalm-api
     */
    public function configureContainer(
        ContainerBuilder $container,
        LoaderInterface $loader
    ): void {
        $container->setParameter('dummy', true);
        $confDir = $this->getProjectDir() . '/config';
        $configFiles = [
            $confDir . '/packages/*' . self::CONFIG_EXTS,
            $confDir . '/services' . self::CONFIG_EXTS,
            $confDir . '/services_' . $this->environment . self::CONFIG_EXTS,
        ];

        $envDir = $confDir . '/packages/' . $this->environment;
        if (is_dir($envDir)) {
            $configFiles[] = $envDir . '/**/*' . self::CONFIG_EXTS;
        }

        array_walk(
            $configFiles,
            static fn ($filePattern) => $loader->load($filePattern, 'glob')
        );

        array_walk(
            $this->extraConfigs,
            static fn ($extraConfig) => $loader->load($extraConfig)
        );
    }
}
