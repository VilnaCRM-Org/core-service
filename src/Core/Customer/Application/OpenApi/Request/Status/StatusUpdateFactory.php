<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\OpenApi\Request\Status;

use App\Shared\Application\OpenApi\Builder\RequestBuilderInterface;
use App\Shared\Application\OpenApi\Builder\RequestPatchBuilder;

final class StatusUpdateFactory extends CustomerStatusRequestFactory
{
    public function __construct(private RequestPatchBuilder $requestBuilder)
    {
    }

    protected function getRequestBuilder(): RequestBuilderInterface
    {
        return $this->requestBuilder;
    }
}
