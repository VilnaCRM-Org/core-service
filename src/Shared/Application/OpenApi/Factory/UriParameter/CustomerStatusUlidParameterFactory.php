<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\UriParameter;

use App\Shared\Application\Fixture\SchemathesisFixtures;

final class CustomerStatusUlidParameterFactory extends UlidParameterFactory
{
    protected function getDescription(): string
    {
        return 'CustomerStatus identifier';
    }

    protected function getExampleUlid(): string
    {
        return SchemathesisFixtures::UPDATE_CUSTOMER_STATUS_ID;
    }

    protected function getDeleteUlid(): string
    {
        return SchemathesisFixtures::DELETE_CUSTOMER_STATUS_ID;
    }

    /**
     * @return array<int, string>
     */
    protected function getAllowedUlids(): array
    {
        return [
            SchemathesisFixtures::CUSTOMER_STATUS_ID,
            SchemathesisFixtures::UPDATE_CUSTOMER_STATUS_ID,
        ];
    }
}
