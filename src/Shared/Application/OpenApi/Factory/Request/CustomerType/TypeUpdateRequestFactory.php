<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Request\CustomerType;

use App\Shared\Application\OpenApi\Builder\RequestBuilderInterface;

final class TypeUpdateRequestFactory extends CustomerTypeRequestFactory
{
    public function __construct(
        private RequestBuilderInterface $requestBuilder,
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
