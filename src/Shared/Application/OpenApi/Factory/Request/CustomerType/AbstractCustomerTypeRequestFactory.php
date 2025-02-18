<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Request\CustomerType;

use ApiPlatform\OpenApi\Model\RequestBody;
use App\Shared\Application\OpenApi\Builder\Parameter;
use App\Shared\Application\OpenApi\Builder\RequestBuilderInterface;
use App\Shared\Application\OpenApi\Factory\Request\AbstractRequestFactoryInterface;

abstract class AbstractCustomerTypeRequestFactory implements AbstractRequestFactoryInterface
{
    abstract protected function getRequestBuilder(): RequestBuilderInterface;

    public function getRequest(): RequestBody
    {
        return $this->getRequestBuilder()->build(
            $this->getDefaultParameters()
        );
    }

    protected function getDefaultParameters(): array
    {
        return [
            $this->getValueParam(),
        ];
    }

    protected function getValueParam(): Parameter
    {
        return new Parameter('value', 'string', 'Prospect', 255);
    }
}
