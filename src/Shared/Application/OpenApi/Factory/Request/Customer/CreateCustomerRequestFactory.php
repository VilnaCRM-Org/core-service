<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Request\Customer;

use App\Shared\Application\OpenApi\Builder\RequestBuilderInterface;

final class CreateCustomerRequestFactory extends CustomerRequestFactory
{
    public function __construct(private RequestBuilderInterface $requestBuilder)
    {
    }

    #[\Override]
    protected function getRequestBuilder(): RequestBuilderInterface
    {
        return $this->requestBuilder;
    }
}
