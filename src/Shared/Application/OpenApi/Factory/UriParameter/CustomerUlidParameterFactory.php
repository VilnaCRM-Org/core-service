<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\UriParameter;

use App\Shared\Application\Fixture\SchemathesisFixtures;

final class CustomerUlidParameterFactory extends UlidParameterFactory
{
    protected function getDescription(): string
    {
        return 'Customer identifier';
    }

    protected function getExampleUlid(): string
    {
        return SchemathesisFixtures::UPDATE_CUSTOMER_ID;
    }
}
