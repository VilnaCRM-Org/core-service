<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\OpenApi\UriParameter;

use App\Shared\Application\OpenApi\Factory\UriParameter\UlidUriParameterFactory;

final class CustomerStatusFactory extends UlidUriParameterFactory
{
    protected function getDescription(): string
    {
        return 'CustomerStatus identifier';
    }
}
