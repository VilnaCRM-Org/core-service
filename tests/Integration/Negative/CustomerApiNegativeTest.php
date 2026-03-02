<?php

declare(strict_types=1);

namespace App\Tests\Integration\Negative;

use Symfony\Component\HttpFoundation\Response;

final class CustomerApiNegativeTest extends BaseNegativeApiTest
{
    public function testGetCustomersCollection500Error(): void
    {
        $this->sendRequest('GET', '/api/customers');
    }

    public function testGetCustomer500Error(): void
    {
        $customerId = $this->faker->ulid();
        $this->sendRequest('GET', "/api/customers/{$customerId}");
    }

    public function testCreateCustomer400Error(): void
    {
        $payload = [
            'name' => $this->faker->name(),
            'email' => $this->faker->email(),
            'initials' => $this->faker->randomLetter(),
            'phone' => $this->faker->phoneNumber(),
            'leadSource' => 'website',
            'type' => '/api/customer_types/1',
            'status' => '/api/customer_statuses/1',
        ];

        $this->sendRequest(
            'POST',
            '/api/customers',
            $payload,
            [],
            Response::HTTP_BAD_REQUEST
        );
    }

    public function testUpdateCustomer500Error(): void
    {
        $customerId = $this->faker->ulid();
        $payload = [
            'name' => $this->faker->name(),
            'email' => $this->faker->email(),
            'initials' => $this->faker->randomLetter(),
            'phone' => $this->faker->phoneNumber(),
            'leadSource' => 'website',
            'type' => '/api/customer_types/1',
            'status' => '/api/customer_statuses/1',
        ];

        $this->sendRequest(
            'PUT',
            "/api/customers/{$customerId}",
            $payload
        );
    }

    public function testDeleteCustomer500Error(): void
    {
        $customerId = $this->faker->ulid();
        $this->sendRequest('DELETE', "/api/customers/{$customerId}");
    }

    public function testPatchCustomer500Error(): void
    {
        $customerId = $this->faker->ulid();
        $payload = [
            'name' => $this->faker->name(),
            'initials' => $this->faker->randomLetter(),
            'phone' => $this->faker->phoneNumber(),
            'leadSource' => 'website',
            'type' => '/api/customer_types/1',
            'status' => '/api/customer_statuses/1',
        ];

        $this->sendRequest(
            'PATCH',
            "/api/customers/{$customerId}",
            $payload,
            [],
            Response::HTTP_INTERNAL_SERVER_ERROR,
            'application/merge-patch+json'
        );
    }
}
