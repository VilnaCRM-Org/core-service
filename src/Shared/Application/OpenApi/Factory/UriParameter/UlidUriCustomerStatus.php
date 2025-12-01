<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\UriParameter;

final class UlidUriCustomerStatus extends UlidUriParameterFactory
{
    protected function getDescription(): string
    {
        return 'CustomerStatus identifier';
    }
}
