<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Response\CustomerStatus;

use App\Shared\Application\OpenApi\Builder\ResponseBuilder;

final class CustomerStatusReturnedResponseFactory extends CustomerStatusResponseFactory
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
        return 'CustomerStatus returned';
    }
}
