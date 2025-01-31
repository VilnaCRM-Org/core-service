<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Response\CustomerType;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Application\OpenApi\Builder\Parameter;
use App\Shared\Application\OpenApi\Builder\ResponseBuilderInterface;
use App\Shared\Application\OpenApi\Factory\Response\ResponseFactoryInterface;

abstract class CustomerTypeResponseFactory implements ResponseFactoryInterface
{
    abstract protected function getResponseBuilder(): ResponseBuilderInterface;

    abstract protected function getTitle(): string;

    public function getResponse(): Response
    {
        return $this->getResponseBuilder()->build(
            $this->getTitle(),
            $this->getDefaultParameters(),
            []
        );
    }

    protected function getDefaultParameters(): array
    {
        return [
            $this->getInitialsParam(),
            $this->getIdParam(),
        ];
    }

    protected function getInitialsParam(): Parameter
    {
        return new Parameter('value', 'string', 'Prospect');
    }

    protected function getIdParam(): Parameter
    {
        return new Parameter('id', 'string', '018dd6ba-e901-7a8c-b27d-65d122caca6b');
    }
}
