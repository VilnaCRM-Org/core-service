<?php

declare(strict_types=1);

namespace App\Shared;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

final class AppKernel extends BaseKernel
{
    use MicroKernelTrait;

    private array $extraConfigs = [];

    const CONFIG_EXTS = '.{php,xml,yaml,yml}';
    public function __construct(string $environment, bool $debug, array $extraConfigs = [])
    {
        parent::__construct($environment, $debug);
        $this->extraConfigs = $extraConfigs;
    }
    public function configureContainer(ContainerBuilder $container, LoaderInterface $loader)
    {
        $confDir = $this->getProjectDir() . '/config';
        $loader->load($confDir . '/packages/*' . self::CONFIG_EXTS, 'glob');
        if (is_dir($confDir . '/packages/' . $this->environment)) {
            $loader->load($confDir . '/packages/' . $this->environment . '/**/*' . self::CONFIG_EXTS, 'glob');
        }
        $loader->load($confDir . '/services' . self::CONFIG_EXTS, 'glob');
        $loader->load($confDir . '/services_' . $this->environment . self::CONFIG_EXTS, 'glob');
        foreach ($this->extraConfigs as $extraConfig) {
            $loader->load($extraConfig);
        }
    }
}