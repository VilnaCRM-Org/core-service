<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Response;

final class CustomerCreatedResponseFactory extends AbstractCustomerResponseFactory
{
    protected function getTitle(): string
    {
        return 'Customer created';
    }
}
