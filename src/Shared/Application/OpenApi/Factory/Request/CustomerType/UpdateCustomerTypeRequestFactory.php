<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Request\CustomerType;

use App\Shared\Application\OpenApi\Builder\RequestBuilderInterface;
use App\Shared\Application\OpenApi\Builder\RequestPatchBuilder;

final class UpdateCustomerTypeRequestFactory extends CustomerTypeRequestFactory
{
    public function __construct(
        private RequestPatchBuilder $requestBuilder,
        string $defaultValue = 'Prospect',
        int $maxLength = 255
    ) {
        parent::__construct($defaultValue, $maxLength);
    }

    protected function getRequestBuilder(): RequestBuilderInterface
    {
        return $this->requestBuilder;
    }
}
