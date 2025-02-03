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
            'id',
            $this->getDescription(),
            true,
            '2b10b7a3-67f0-40ea-a367-44263321592a',
            'string'
        );
    }

    protected function getDescription(): string
    {
        return 'Customer identifier';
    }
}
