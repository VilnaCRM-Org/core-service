<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Unit\UlidProvider;
use Faker\Factory;
use Faker\Generator;

final class CustomerApiTest extends ApiTestCase
{
    private Generator $faker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->faker = Factory::create();
        $this->faker->addProvider(new UlidProvider($this->faker));
    }

    /**
     * Positive: Retrieve the customers collection.
     */
    public function testGetCustomersCollection(): void
    {
        $client = self::createClient();
        $response = $client->request('GET', '/api/customers');
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);
        $this->assertIsArray($data['member']);
        $this->assertArrayHasKey('totalItems', $data);
    }

    /**
     * Negative: Invalid query parameter (non-integer value for page).
     */
    public function testGetCustomersCollectionInvalidQuery(): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/customers', [
            'query' => ['page' => 'invalid']
        ]);
        $this->assertResponseStatusCodeSame(400);
    }

    /**
     * Positive: Create a new customer.
     */
    public function testCreateCustomer(): void
    {
        $client = self::createClient();
        $payload = [
            'email' => $this->faker->unique()->email(),
            'phone' => '0123456789',
            'initials' => 'Name Surname',
            'leadSource' => 'Google',
            'type' => $this->createCustomerType(),
            'status' => $this->createCustomerStatus(),
            'confirmed' => true,
        ];

        $response = $client->request('POST', '/api/customers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $this->assertSuccessfulCreation($payload, $response->toArray());
    }

    /**
     * Negative: Create customer with missing required fields (e.g. email).
     */
    public function testCreateCustomerMissingFields(): void
    {
        $client = self::createClient();
        $payload = [
            // 'email' is intentionally missing
            'phone' => '0123456789',
            'initials' => 'Missing Email',
            'leadSource' => 'Google',
            'type' => $this->createCustomerType(),
            'status' => $this->createCustomerStatus(),
            'confirmed' => true,
        ];

        $client->request('POST', '/api/customers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);
        $this->assertResponseStatusCodeSame(422);
    }

    /**
     * Negative: Create customer with an invalid email format.
     */
    public function testCreateCustomerInvalidEmail(): void
    {
        $client = self::createClient();
        $payload = [
            'email' => 'not-an-email',
            'phone' => '0123456789',
            'initials' => 'Invalid Email',
            'leadSource' => 'Google',
            'type' => $this->createCustomerType(),
            'status' => $this->createCustomerStatus(),
            'confirmed' => true,
        ];

        $client->request('POST', '/api/customers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);
        $this->assertResponseStatusCodeSame(422);
    }

    /**
     * Positive: Get a single customer.
     */
    public function testGetCustomer(): void
    {
        $payload = $this->createCustomerPayload('Get Customer');
        $customerIri = $this->createCustomer($payload);
        $client = self::createClient();
        $response = $client->request('GET', $customerIri);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertSame($payload['email'], $data['email']);
    }

    /**
     * Negative: Get a non-existent customer.
     */
    public function testGetCustomerNotFound(): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/customers/non-existent-ulid');
        $this->assertResponseStatusCodeSame(404);
    }

    /**
     * Positive: Replace an existing customer using PUT.
     */
    public function testReplaceCustomer(): void
    {
        $payload = $this->createCustomerPayload('Replace Customer');
        $customerIri = $this->createCustomer($payload);

        $updatedPayload = [
            'email' => $this->faker->unique()->email(),
            'phone' => '1112223333',
            'initials' => 'Replaced Customer',
            'leadSource' => 'Yahoo',
            'type' => $this->createCustomerType(),
            'status' => $this->createCustomerStatus(),
            'confirmed' => false,
        ];

        $client = self::createClient();
        $response = $client->request('PUT', $customerIri, [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($updatedPayload),
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertSame($updatedPayload['email'], $data['email']);
    }

    /**
     * Negative: Replace customer with missing required field (e.g. email).
     */
    public function testReplaceCustomerMissingField(): void
    {
        $payload = $this->createCustomerPayload('Replace Missing Field');
        $customerIri = $this->createCustomer($payload);

        $updatedPayload = [
            // Omitting the email field
            'phone' => '1112223333',
            'initials' => 'Replaced Customer',
            'leadSource' => 'Yahoo',
            'type' => $this->createCustomerType(),
            'status' => $this->createCustomerStatus(),
            'confirmed' => false,
        ];

        $client = self::createClient();
        $client->request('PUT', $customerIri, [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($updatedPayload),
        ]);
        $this->assertResponseStatusCodeSame(422);
    }

    /**
     * Negative: Replace a non-existent customer.
     */
    public function testReplaceCustomerNotFound(): void
    {
        $client = self::createClient();

        $updatedPayload = [
            'email' => $this->faker->unique()->email(),
            'phone' => '1112223333',
            'initials' => 'Non-existent Customer',
            'leadSource' => 'Yahoo',
            'type' => $this->createCustomerType(),
            'status' => $this->createCustomerStatus(),
            'confirmed' => false,
        ];

        $client->request('PUT', '/api/customers/non-existent-ulid', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($updatedPayload),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    /**
     * Positive: Partially update a customer using PATCH.
     */
    public function testPatchCustomer(): void
    {
        $payload = $this->createCustomerPayload('Patch Customer');
        $customerIri = $this->createCustomer($payload);
        $patchPayload = ['email' => $this->faker->unique()->email()];

        $client = self::createClient();
        $response = $client->request('PATCH', $customerIri, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'body' => json_encode($patchPayload),
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertSame($patchPayload['email'], $data['email']);
    }

    /**
     * Negative: Partial update with an invalid email.
     */
    public function testPatchCustomerInvalidEmail(): void
    {
        $payload = $this->createCustomerPayload('Patch Invalid Email');
        $customerIri = $this->createCustomer($payload);
        $patchPayload = ['email' => 'invalid-email'];

        $client = self::createClient();
        $client->request('PATCH', $customerIri, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'body' => json_encode($patchPayload),
        ]);
        $this->assertResponseStatusCodeSame(422);
    }

    /**
     * Negative: Partial update on a non-existent customer.
     */
    public function testPatchCustomerNotFound(): void
    {
        $client = self::createClient();
        $patchPayload = ['email' => $this->faker->unique()->email()];
        $client->request('PATCH', '/api/customers/non-existent-ulid', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'body' => json_encode($patchPayload),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    /**
     * Positive: Delete a customer.
     */
    public function testDeleteCustomer(): void
    {
        $payload = $this->createCustomerPayload('Delete Customer');
        $customerIri = $this->createCustomer($payload);
        $client = self::createClient();

        $client->request('DELETE', $customerIri);
        $this->assertResponseStatusCodeSame(204);

        // Verify that the customer has been removed.
        $client->request('GET', $customerIri);
        $this->assertResponseStatusCodeSame(404);
    }

    /**
     * Negative: Delete a non-existent customer.
     */
    public function testDeleteCustomerNotFound(): void
    {
        $client = self::createClient();
        $client->request('DELETE', '/api/customers/non-existent-ulid');
        $this->assertResponseStatusCodeSame(404);
    }

    /*
     * Helper Methods below (similar to your original tests)
     */

    /**
     * Asserts that a customer was successfully created.
     *
     * @param array<string, mixed> $payload The customer creation payload
     * @param array<string, mixed> $data    The API response data
     */
    private function assertSuccessfulCreation(array $payload, array $data): void
    {
        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertArrayHasKey('@id', $data, "Response should include '@id' key");
        $this->assertSame($payload['email'], $data['email']);
    }

    /**
     * Creates a customer resource.
     *
     * @param array<string, mixed> $payload
     */
    private function createCustomer(array $payload): string
    {
        $client = self::createClient();
        $response = $client->request('POST', '/api/customers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);
        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();
        return $data['@id'];
    }

    /**
     * @return array<string, mixed>
     */
    private function createCustomerPayload(string $initials = 'Test Customer'): array
    {
        return [
            'email' => $this->faker->unique()->email(),
            'phone' => '0123456789',
            'initials' => $initials,
            'leadSource' => 'Google',
            'type' => $this->createCustomerType(),
            'status' => $this->createCustomerStatus(),
            'confirmed' => true,
        ];
    }

    /**
     * Creates a customer status and returns its IRI.
     */
    private function createCustomerStatus(): string
    {
        $client = self::createClient();
        $payload = ['value' => 'Active'];
        $response = $client->request('POST', '/api/customer_statuses', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);
        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();
        $this->assertSame('CustomerStatus', $data['@type']);
        return $data['@id'];
    }

    /**
     * Creates a customer type and returns its IRI.
     */
    private function createCustomerType(): string
    {
        $client = self::createClient();
        $payload = ['value' => 'Prospect'];
        $response = $client->request('POST', '/api/customer_types', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);
        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();
        $this->assertSame('CustomerType', $data['@type']);
        return $data['@id'];
    }
}
