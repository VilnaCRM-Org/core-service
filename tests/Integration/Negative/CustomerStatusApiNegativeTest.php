<?php

declare(strict_types=1);

namespace App\Tests\Integration\Negative;

use Symfony\Component\HttpFoundation\Response;

final class CustomerStatusApiNegativeTest extends BaseNegativeApiTest
{
    public function testGetCustomerStatusesCollection500Error(): void
    {
        $this->sendRequest('GET', '/api/customer_statuses');
    }

    public function testGetCustomerStatus500Error(): void
    {
        $statusId = $this->faker->ulid();
        $this->sendRequest('GET', "/api/customer_statuses/{$statusId}");
    }

    public function testCreateCustomerStatus500Error(): void
    {
        $payload = [
            'value' => $this->faker->word(),
        ];

        $this->sendRequest(
            'POST',
            '/api/customer_statuses',
            $payload
        );
    }

    public function testUpdateCustomerStatus500Error(): void
    {
        $statusId = $this->faker->ulid();
        $payload = [
            'value' => $this->faker->word(),
        ];

        $this->sendRequest(
            'PUT',
            "/api/customer_statuses/{$statusId}",
            $payload
        );
    }

    public function testDeleteCustomerStatus500Error(): void
    {
        $statusId = $this->faker->ulid();
        $this->sendRequest('DELETE', "/api/customer_statuses/{$statusId}");
    }

    public function testPatchCustomerStatus500Error(): void
    {
        $statusId = $this->faker->ulid();
        $payload = [
            'value' => $this->faker->word(),
        ];

        $this->sendRequest(
            'PATCH',
            "/api/customer_statuses/{$statusId}",
            $payload,
            [],
            Response::HTTP_INTERNAL_SERVER_ERROR,
            'application/merge-patch+json'
        );
    }
}
