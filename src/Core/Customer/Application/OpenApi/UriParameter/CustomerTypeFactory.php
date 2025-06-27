<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\OpenApi\UriParameter;

use App\Shared\Application\OpenApi\Factory\UriParameter\UlidUriParameterFactory;

final class CustomerTypeFactory extends UlidUriParameterFactory
{
    protected function getDescription(): string
    {
        return 'CustomerType identifier';
    }
}
