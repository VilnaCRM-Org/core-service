<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Request\Type;

use App\Shared\Application\OpenApi\Builder\RequestBuilder;
use App\Shared\Application\OpenApi\Builder\RequestBuilderInterface;

final class TypeCreateFactory extends CustomerTypeRequestFactory
{
    public function __construct(private RequestBuilder $requestBuilder)
    {
    }

    protected function getRequestBuilder(): RequestBuilderInterface
    {
        return $this->requestBuilder;
    }
}
