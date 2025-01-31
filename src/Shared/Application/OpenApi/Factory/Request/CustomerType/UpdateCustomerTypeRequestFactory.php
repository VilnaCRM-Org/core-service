<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Request\CustomerType;

use App\Shared\Application\OpenApi\Builder\RequestBuilder;

class UpdateCustomerTypeRequestFactory extends AbstractCustomerTypeRequestFactory
{
    public function __construct(private RequestBuilder $requestBuilder)
    {
    }

    protected function getRequestBuilder(): RequestBuilder
    {
        return $this->requestBuilder;
    }
}
