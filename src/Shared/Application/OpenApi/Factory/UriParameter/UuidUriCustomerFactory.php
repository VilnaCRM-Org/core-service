<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\UriParameter;

class UuidUriCustomerFactory extends AbstractUuidUriParameterFactory
{
    protected function getDescription(): string
    {
        return 'CustomerStatus identifier';
    }
}
