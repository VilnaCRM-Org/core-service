<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\OpenApi\Request\Customer;

use App\Shared\Application\OpenApi\Builder\RequestBuilder;
use App\Shared\Application\OpenApi\Builder\RequestBuilderInterface;

final class CreateFactory extends CustomerRequestFactory
{
    public function __construct(private readonly RequestBuilder $requestBuilder)
    {
    }

    protected function getRequestBuilder(): RequestBuilderInterface
    {
        return $this->requestBuilder;
    }
}
