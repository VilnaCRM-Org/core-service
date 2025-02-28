<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Request\Type;

use App\Shared\Application\OpenApi\Builder\RequestBuilderInterface;
use App\Shared\Application\OpenApi\Builder\RequestPatchBuilder;

final class TypeUpdateFactory extends CustomerTypeRequestFactory
{
    public function __construct(private RequestPatchBuilder $requestBuilder)
    {
    }

    protected function getRequestBuilder(): RequestBuilderInterface
    {
        return $this->requestBuilder;
    }
}
