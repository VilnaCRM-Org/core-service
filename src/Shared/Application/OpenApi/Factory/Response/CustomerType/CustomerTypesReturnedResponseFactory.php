<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Response\CustomerType;

use App\Shared\Application\OpenApi\Builder\ArrayResponseBuilder;

final class CustomerTypesReturnedResponseFactory extends CustomerTypeResponseFactory
{
    public function __construct(private ArrayResponseBuilder $responseBuilder)
    {
    }

    protected function getResponseBuilder(): ArrayResponseBuilder
    {
        return $this->responseBuilder;
    }

    protected function getTitle(): string
    {
        return 'CustomerTypes returned';
    }
}
