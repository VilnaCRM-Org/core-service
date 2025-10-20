<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Request\CustomerStatus;

use App\Shared\Application\OpenApi\Builder\RequestBuilderInterface;
use App\Shared\Application\OpenApi\Builder\RequestPatchBuilder;

final class UpCStReq extends
    CustomerStatusRequestFactory
{
    public function __construct(private RequestPatchBuilder $requestBuilder)
    {
    }

    protected function getRequestBuilder(): RequestBuilderInterface
    {
        return $this->requestBuilder;
    }
}
