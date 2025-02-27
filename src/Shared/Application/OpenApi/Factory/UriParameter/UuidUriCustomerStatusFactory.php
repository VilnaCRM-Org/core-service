<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\UriParameter;

class UuidUriCustomerStatusFactory extends AbstractUuidUriParameterFactory
{
    protected function getDescription(): string
    {
        return 'CustomerStatus identifier';
    }
}
