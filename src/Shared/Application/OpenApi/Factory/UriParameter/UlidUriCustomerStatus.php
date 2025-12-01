<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\UriParameter;

final class UlidUriCustomerStatus extends UlidUriParameterFactory
{
    protected function getDescription(): string
    {
        return 'CustomerStatus identifier';
    }

    protected function getExampleUlid(): string
    {
        return '01JKX8XNHQPR7BJZXM9W2K5T3Y';
    }
}
