<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\UriParameter;

use ApiPlatform\OpenApi\Model\Parameter;
use App\Shared\Application\OpenApi\Builder\UriParameterBuilder;

abstract class UlidParameterFactory implements UriParameterFactoryInterface
{
    public function __construct(private UriParameterBuilder $parameterBuilder)
    {
    }

    #[\Override]
    public function getParameter(): Parameter
    {
        return $this->parameterBuilder->build(
            'ulid',
            $this->getDescription(),
            true,
            $this->getExampleUlid(),
            'string'
        );
    }

    abstract protected function getDescription(): string;

    abstract protected function getExampleUlid(): string;
}
