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

    public function testUpdateCustomer(): void
    {
        $payload = $this->createCustomerPayload('Update Customer');
        $customerIri = $this->createCustomer($payload);

        $updatedPayload = [
            'email' => $this->faker->unique()->email(),
            'phone' => '0987654321',
            'initials' => 'Updated Customer',
            'leadSource' => 'Bing',
            'type' => $this->createCustomerType(),
            'status' => $this->createCustomerStatus(),
            'confirmed' => false,
        ];

        $client = self::createClient();
        $response = $client->request('PATCH', $customerIri, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'body' => json_encode($updatedPayload),
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertSame($updatedPayload['email'], $data['email']);
    }

    public function testPartialUpdateCustomer(): void
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

    public function testDeleteCustomer(): void
    {
        $payload = $this->createCustomerPayload('Delete Customer');
        $customerIri = $this->createCustomer($payload);

        $deleteClient = self::createClient();
        $deleteClient->request('DELETE', $customerIri);
        $this->assertResponseStatusCodeSame(204);

        $getClient = self::createClient();
        $getClient->request('GET', $customerIri);
        $this->assertResponseStatusCodeSame(404);
    }

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
     * Asserts that a customer was successfully created.
     *
     * @param array<string, string|bool|array<string, string>> $payload The customer creation payload
     * @param array<string, string|int|bool|array<string, string>> $data The API response data
     */
    private function assertSuccessfulCreation(array $payload, array $data): void
    {
        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame(
            'content-type',
            'application/ld+json; charset=utf-8'
        );
        $this->assertArrayHasKey(
            '@id',
            $data,
            "Response should include '@id' key"
        );
        $this->assertSame($payload['email'], $data['email']);
    }

    /**
     * @param array<string, string|bool|array<string, string>> $payload
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
     * @return array<string, string|bool|array<string, string>>
     */
    private function createCustomerPayload(
        string $initials = 'Test Customer'
    ): array {
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
     *
     * @return string The IRI of the created customer status
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
     *
     * @return string The IRI of the created customer type
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
