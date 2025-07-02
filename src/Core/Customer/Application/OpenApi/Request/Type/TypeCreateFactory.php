<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\OpenApi\Request\Type;

use App\Shared\Application\OpenApi\Builder\RequestBuilder;
use App\Shared\Application\OpenApi\Builder\RequestBuilderInterface;

final class TypeCreateFactory extends CustomerTypeRequestFactory
{
    public function __construct(private RequestBuilder $requestBuilder)
    {
    }

    /**
     * @return RequestBuilder
     */
    protected function getRequestBuilder(): RequestBuilderInterface
    {
        return $this->requestBuilder;
    }
}
