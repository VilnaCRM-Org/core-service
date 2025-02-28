<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\UriParameter;

final class CustomerTypeFactory extends UuidUriParameterFactory
{
    protected function getDescription(): string
    {
        return 'CustomerType identifier';
    }
}
