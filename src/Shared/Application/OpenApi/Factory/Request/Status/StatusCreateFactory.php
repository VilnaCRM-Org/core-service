<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Request\Status;

use App\Shared\Application\OpenApi\Builder\RequestBuilder;

final class StatusCreateFactory extends CustomerStatusRequestFactory
{
    public function __construct(private RequestBuilder $requestBuilder)
    {
    }

    protected function getRequestBuilder(): RequestBuilder
    {
        return $this->requestBuilder;
    }
}
