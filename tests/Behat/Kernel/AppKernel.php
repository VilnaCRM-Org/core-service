<?php

declare(strict_types=1);

namespace App\Tests\Behat\Kernel;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

final class AppKernel extends BaseKernel
{
    use MicroKernelTrait;

    public const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    /**
     * @var iterable<string>
     */
    private array $extraConfigs = [];

    /**
     * @param iterable<string> $extraConfigs
     */
    public function __construct(
        string $environment,
        bool $debug,
        array $extraConfigs = []
    ) {
        parent::__construct($environment, $debug);
        $this->extraConfigs = $extraConfigs;
    }

    public function configureContainer(
        ContainerBuilder $container,
        LoaderInterface $loader
    ): void {
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
