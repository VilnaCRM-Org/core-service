<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\UriParameter;

final class CustomerUlidParameterFactory extends UlidParameterFactory
{
    #[Override]
    protected function getDescription(): string
    {
        return 'Customer identifier';
    }

    #[Override]
    protected function getExampleUlid(): string
    {
        return '01JKX8XGHVDZ46MWYMZT94YER4';
    }
}
