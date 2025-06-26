<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\OpenApi\Request\Type;

use ApiPlatform\OpenApi\Model\RequestBody;
use App\Shared\Application\OpenApi\Builder\Parameter;
use App\Shared\Application\OpenApi\Builder\RequestBuilderInterface;
use App\Shared\Application\OpenApi\Factory\Request\RequestFactoryInterface;

abstract class CustomerTypeRequestFactory implements RequestFactoryInterface
{
    public function getRequest(): RequestBody
    {
        return $this->getRequestBuilder()->build(
            $this->getDefaultParameters()
        );
    }

    /**
     * @return Parameter[]
     *
     * @psalm-return list{Parameter}
     */
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

    abstract protected function getRequestBuilder(): RequestBuilderInterface;
}
