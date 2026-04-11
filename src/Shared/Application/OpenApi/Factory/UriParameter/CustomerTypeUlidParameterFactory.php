<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\UriParameter;

use App\Shared\Application\Fixture\SchemathesisFixtures;

final class CustomerTypeUlidParameterFactory extends UlidParameterFactory
{
    protected function getDescription(): string
    {
        return 'CustomerType identifier';
    }

    protected function getExampleUlid(): string
    {
        return SchemathesisFixtures::UPDATE_CUSTOMER_TYPE_ID;
    }
}
