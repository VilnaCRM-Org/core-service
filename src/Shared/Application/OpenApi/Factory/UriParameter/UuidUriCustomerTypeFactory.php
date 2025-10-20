<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\UriParameter;

final class UuidUriCustomerTypeFactory extends AbstractUuidUriParameterFactory
{
    protected function getDescription(): string
    {
        return 'CustomerType identifier';
    }
}
