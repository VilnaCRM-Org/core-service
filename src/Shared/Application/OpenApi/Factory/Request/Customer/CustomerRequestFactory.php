<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Request\Customer;

use App\Shared\Application\OpenApi\Builder\RequestBuilder;

final class CustomerRequestFactory extends AbstractCustomerRequestFactory
{
    public function __construct(private RequestBuilder $requestBuilder)
    {
    }

    protected function getRequestBuilder(): RequestBuilder
    {
        return $this->requestBuilder;
    }
}
