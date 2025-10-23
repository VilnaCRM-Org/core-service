<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Request\CustomerType;

use App\Shared\Application\OpenApi\Builder\RequestBuilder;

final class CreateCustomerTypeRequestFactory extends CustomerTypeRequestFactory
{
    public function __construct(
        private RequestBuilder $requestBuilder,
        string $defaultValue = 'Prospect',
        int $maxLength = 255
    ) {
        parent::__construct($defaultValue, $maxLength);
    }

    protected function getRequestBuilder(): RequestBuilder
    {
        return $this->requestBuilder;
    }
}
