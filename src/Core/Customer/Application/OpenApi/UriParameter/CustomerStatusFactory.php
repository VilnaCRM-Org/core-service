<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\OpenApi\UriParameter;

use App\Shared\Application\OpenApi\Factory\UriParameter\UlidUriParameterFactory;

final class CustomerStatusFactory extends UlidUriParameterFactory
{
    /**
     * @return string
     *
     * @psalm-return 'CustomerStatus identifier'
     */
    protected function getDescription(): string
    {
        return 'CustomerStatus identifier';
    }
}
