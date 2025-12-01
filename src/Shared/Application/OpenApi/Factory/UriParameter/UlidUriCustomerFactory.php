<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\UriParameter;

final class UlidUriCustomerFactory extends UlidUriParameterFactory
{
    protected function getDescription(): string
    {
        return 'Customer identifier';
    }

    protected function getExampleUlid(): string
    {
        return '01JKX8XGHVDZ46MWYMZT94YER4';
    }
}
