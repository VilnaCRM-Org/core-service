<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Response;

use App\Shared\Application\OpenApi\Builder\ArrayResponseBuilder;

final class CustomersReturnedResponseFactory extends CustomerResponseFactory
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
        return 'Customers returned';
    }
}
