<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\UriParameter;

final class UlidUriCustomerType extends UlidUriParameterFactory
{
    protected function getDescription(): string
    {
        return 'CustomerType identifier';
    }

    protected function getExampleUlid(): string
    {
        return '01JKX8XVJS4F9CHTQR6N8D2PW7';
    }
}
