<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\UriParameter;

use ApiPlatform\OpenApi\Model\Parameter;
use App\Shared\Application\OpenApi\Builder\UriParameterBuilder;

abstract class AbstractUuidUriParameterFactory implements UriParameterFactoryInterface
{
    public function __construct(private UriParameterBuilder $parameterBuilder)
    {
    }

    public function getParameter(): Parameter
    {
        return $this->parameterBuilder->build(
            'ulid',
            $this->getDescription(),
            true,
            '01JKX8XGHVDZ46MWYMZT94YER4',
            'string'
        );
    }

    abstract protected function getDescription(): string;
}
