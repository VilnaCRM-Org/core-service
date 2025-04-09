<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Customer\Domain\Entity\CustomerStatus;
use App\Customer\Domain\Entity\CustomerType;

final class CustomerApiTest extends BaseIntegrationTest
{
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

    public function testGetCustomersCollectionInvalidQuery(): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/customers', [
            'query' => ['page' => 'invalid'],
        ]);
        $this->assertResponseStatusCodeSame(400);
    }

    public function testCreateCustomerSuccess(): void
    {
        $payload = $this->getCustomerPayload('John Doe');
        $iri = $this->createEntity('/api/customers', $payload);
        $client = self::createClient();
        $response = $client->request('GET', $iri);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertSame($payload['email'], $data['email']);
    }

    public function testCreateCustomerFailure(): void
    {
        $payload = [
            'phone' => $this->faker->phoneNumber(),
            'initials' => 'No Email',
            'leadSource' => $this->faker->word(),
            'confirmed' => true,
        ];
        $client = self::createClient();
        $client->request(
            'POST',
            '/api/customers',
            [
                'headers' => ['Content-Type' => 'application/ld+json'],
                'body' => json_encode($payload),
            ]
        );
        $this->assertResponseStatusCodeSame(422);
    }

    public function testGetCustomerSuccess(): void
    {
        $payload = $this->getCustomerPayload('Test Get');
        $iri = $this->createEntity('/api/customers', $payload);
        $client = self::createClient();
        $response = $client->request('GET', $iri);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertSame($payload['email'], $data['email']);
    }

    public function testGetCustomerNotFound(): void
    {
        $ulid = (string) $this->faker->ulid();
        $client = self::createClient();
        $client->request('GET', "/api/customers/{$ulid}");
        $this->assertResponseStatusCodeSame(404);
    }

    public function testReplaceCustomerSuccess(): void
    {
        $payload = $this->getCustomerPayload('Replace Test');
        $iri = $this->createEntity('/api/customers', $payload);

        $updatedPayload = $this->getUpdatedCustomerPayload();
        $this->updateCustomer($iri, $updatedPayload);
        $this->verifyCustomerUpdate($iri, $updatedPayload);
    }

    public function testReplaceCustomerFailure(): void
    {
        $payload = $this->getCustomerPayload('Missing Email');
        $iri = $this->createEntity('/api/customers', $payload);
        $updated = [
            'phone' => '1112223333',
            'initials' => 'No Email Updated',
            'leadSource' => 'Yahoo',
            'type' => $this->createCustomerType(),
            'status' => $this->createCustomerStatus(),
            'confirmed' => false,
        ];
        $client = self::createClient();
        $client->request(
            'PUT',
            $iri,
            [
                'headers' => ['Content-Type' => 'application/ld+json'],
                'body' => json_encode($updated),
            ]
        );
        $this->assertResponseStatusCodeSame(422);
    }

    public function testReplaceCustomerNotFound(): void
    {
        $ulid = (string) $this->faker->ulid();
        $updated = [
            'email' => $this->faker->unique()->email(),
            'phone' => '1112223333',
            'initials' => 'Nonexistent',
            'leadSource' => 'Yahoo',
            'type' => $this->createCustomerType(),
            'status' => $this->createCustomerStatus(),
            'confirmed' => false,
        ];
        $client = self::createClient();
        $client->request(
            'PUT',
            "/api/customers/{$ulid}",
            [
                'headers' => ['Content-Type' => 'application/ld+json'],
                'body' => json_encode($updated),
            ]
        );
        $this->assertResponseStatusCodeSame(404);
    }

    public function testPatchCustomerSuccess(): void
    {
        $payload = $this->getCustomerPayload('Patch Test');
        $iri = $this->createEntity('/api/customers', $payload);
        $patch = [
            'email' => $this->faker->unique()->email(),
        ];
        $client = self::createClient();
        $client->request(
            'PATCH',
            $iri,
            [
                'headers' => ['Content-Type' => 'application/merge-patch+json'],
                'body' => json_encode($patch),
            ]
        );
        $this->assertResponseIsSuccessful();
        $data = (self::createClient()->request('GET', $iri))->toArray();
        $this->assertSame($patch['email'], $data['email']);
    }

    public function testPatchCustomerFailure(): void
    {
        $payload = $this->getCustomerPayload('Patch Fail');
        $iri = $this->createEntity('/api/customers', $payload);
        $patch = ['email' => 'invalid-email'];
        $client = self::createClient();
        $client->request(
            'PATCH',
            $iri,
            [
                'headers' => ['Content-Type' => 'application/merge-patch+json'],
                'body' => json_encode($patch),
            ]
        );
        $this->assertResponseStatusCodeSame(422);
    }

    public function testPatchCustomerNotFound(): void
    {
        $ulid = (string) $this->faker->ulid();
        $patch = ['email' => $this->faker->unique()->email()];
        $client = self::createClient();
        $client->request(
            'PATCH',
            "/api/customers/{$ulid}",
            [
                'headers' => ['Content-Type' => 'application/merge-patch+json'],
                'body' => json_encode($patch),
            ]
        );
        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteCustomerSuccess(): void
    {
        $payload = $this->getCustomerPayload('Delete Test');
        $iri = $this->createEntity('/api/customers', $payload);
        $client = self::createClient();
        $client->request('DELETE', $iri);
        $this->assertResponseStatusCodeSame(204);
        $client->request('GET', $iri);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteCustomerNotFound(): void
    {
        $ulid = (string) $this->faker->ulid();
        $client = self::createClient();
        $client->request('DELETE', "/api/customers/{$ulid}");
        $this->assertResponseStatusCodeSame(404);
    }

    /**
     * @return array<string, CustomerStatus, CustomerType, string, bool>
     */
    private function getUpdatedCustomerPayload(): array
    {
        return [
            'email' => $this->faker->unique()->email(),
            'phone' => '1112223333',
            'initials' => 'Replaced',
            'leadSource' => 'Yahoo',
            'type' => $this->createCustomerType(),
            'status' => $this->createCustomerStatus(),
            'confirmed' => false,
        ];
    }

    /**
     * @param array<string, string> $payload
     */
    private function updateCustomer(string $iri, array $payload): void
    {
        $client = self::createClient();
        $client->request(
            'PUT',
            $iri,
            [
                'headers' => ['Content-Type' => 'application/ld+json'],
                'body' => json_encode($payload),
            ]
        );
        $this->assertResponseIsSuccessful();
    }

    /**
     * @param array<string, CustomerStatus, CustomerType, string, bool> $payload
     */
    private function verifyCustomerUpdate(string $iri, array $payload): void
    {
        $data = (self::createClient()->request('GET', $iri))->toArray();
        $this->assertSame($payload['email'], $data['email']);
    }

    /**
     * @return array<string, string|bool|CustomerType|CustomerStatus>
     */
    private function getCustomerPayload(string $name = 'Test Customer'): array
    {
        return [
            'email' => $this->faker->unique()->email(),
            'phone' => $this->faker->phoneNumber(),
            'initials' => $name,
            'leadSource' => $this->faker->word(),
            'type' => $this->createCustomerType(),
            'status' => $this->createCustomerStatus(),
            'confirmed' => true,
        ];
    }

    private function createCustomerStatus(): string
    {
        return $this->createEntity(
            '/api/customer_statuses',
            ['value' => $this->faker->word()],
            'CustomerStatus'
        );
    }

    private function createCustomerType(): string
    {
        return $this->createEntity(
            '/api/customer_types',
            ['value' => $this->faker->word()],
            'CustomerType'
        );
    }
}
