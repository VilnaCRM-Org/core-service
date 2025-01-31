<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Response\CustomerType;

use App\Shared\Application\OpenApi\Builder\ResponseBuilder;

final class CustomerTypeReturnedResponseFactory extends CustomerTypeResponseFactory
{
    public function __construct(private ResponseBuilder $responseBuilder)
    {
    }

    protected function getResponseBuilder(): ResponseBuilder
    {
        return $this->responseBuilder;
    }

    protected function getTitle(): string
    {
        return 'CustomerType returned';
    }
}
