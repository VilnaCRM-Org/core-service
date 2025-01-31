<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Response\Customer;

use App\Shared\Application\OpenApi\Builder\ResponseBuilder;

class CustomerUpdatedResponseFactory extends CustomerResponseFactory
{
    public function __construct(private ResponseBuilder $responseBuilder)
    {
    }

    protected function getResponseBuilder(): ResponseBuilder
    {
        return $this->responseBuilder;
    }

    protected function getTitle(): string
    {
        return 'Customer updated';
    }
}
