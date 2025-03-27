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

    public function testGetCustomersCollectionInvalidQuery(): void
    {
        $client = self::createClient();
        $client->request(
            'GET',
            '/api/customers',
            [
                'query' => [
                    'page' => 'invalid',
                ],
            ]
        );
        $this->assertResponseStatusCodeSame(400);
    }

    public function testCreateCustomerSuccess(): void
    {
        $client = self::createClient();
        $payload = $this->getCustomerPayload('John Doe');
        $response = $client->request(
            'POST',
            '/api/customers',
            [
                'headers' => [
                    'Content-Type' => 'application/ld+json',
                ],
                'body' => json_encode($payload),
            ]
        );
        $this->assertSuccessfulCreation(
            $payload,
            $response->toArray()
        );
    }

    public function testCreateCustomerFailure(): void
    {
        $client = self::createClient();
        $payload = [
            'phone' => '0123456789',
            'initials' => 'No Email',
            'leadSource' => 'Google',
            'confirmed' => true,
        ];
        $client->request(
            'POST',
            '/api/customers',
            [
                'headers' => [
                    'Content-Type' => 'application/ld+json',
                ],
                'body' => json_encode($payload),
            ]
        );
        $this->assertResponseStatusCodeSame(422);
    }

    public function testGetCustomerSuccess(): void
    {
        $payload = $this->getCustomerPayload('Test Get');
        $iri = $this->createCustomer($payload);
        $client = self::createClient();
        $response = $client->request('GET', $iri);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertSame($payload['email'], $data['email']);
    }

    public function testGetCustomerNotFound(): void
    {
        $client = self::createClient();
        $ulid = (string) $this->faker->ulid();
        $client->request('GET', "/api/customers/{$ulid}");
        $this->assertResponseStatusCodeSame(404);
    }

    public function testReplaceCustomerSuccess(): void
    {
        $payload = $this->getCustomerPayload('Replace Test');
        $iri = $this->createCustomer($payload);
        $updatedPayload = [
            'email' => $this->faker->unique()->email(),
            'phone' => '1112223333',
            'initials' => 'Replaced',
            'leadSource' => 'Yahoo',
            'type' => $this->createCustomerType(),
            'status' => $this->createCustomerStatus(),
            'confirmed' => false,
        ];
        $client = self::createClient();
        $response = $client->request(
            'PUT',
            $iri,
            [
                'headers' => [
                    'Content-Type' => 'application/ld+json',
                ],
                'body' => json_encode($updatedPayload),
            ]
        );
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertSame(
            $updatedPayload['email'],
            $data['email']
        );
    }

    public function testReplaceCustomerFailure(): void
    {
        $payload = $this->getCustomerPayload('Missing Email');
        $iri = $this->createCustomer($payload);
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
                'headers' => [
                    'Content-Type' => 'application/ld+json',
                ],
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
                'headers' => [
                    'Content-Type' => 'application/ld+json',
                ],
                'body' => json_encode($updated),
            ]
        );
        $this->assertResponseStatusCodeSame(404);
    }

    public function testPatchCustomerSuccess(): void
    {
        $payload = $this->getCustomerPayload('Patch Test');
        $iri = $this->createCustomer($payload);
        $patch = [
            'email' => $this->faker->unique()->email(),
        ];
        $client = self::createClient();
        $response = $client->request(
            'PATCH',
            $iri,
            [
                'headers' => [
                    'Content-Type' =>
                        'application/merge-patch+json',
                ],
                'body' => json_encode($patch),
            ]
        );
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertSame($patch['email'], $data['email']);
    }

    public function testPatchCustomerFailure(): void
    {
        $payload = $this->getCustomerPayload('Patch Fail');
        $iri = $this->createCustomer($payload);
        $patch = [
            'email' => 'invalid-email',
        ];
        $client = self::createClient();
        $client->request(
            'PATCH',
            $iri,
            [
                'headers' => [
                    'Content-Type' =>
                        'application/merge-patch+json',
                ],
                'body' => json_encode($patch),
            ]
        );
        $this->assertResponseStatusCodeSame(422);
    }

    public function testPatchCustomerNotFound(): void
    {
        $ulid = (string) $this->faker->ulid();
        $client = self::createClient();
        $patch = [
            'email' => $this->faker->unique()->email(),
        ];
        $client->request(
            'PATCH',
            "/api/customers/{$ulid}",
            [
                'headers' => [
                    'Content-Type' =>
                        'application/merge-patch+json',
                ],
                'body' => json_encode($patch),
            ]
        );
        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteCustomerSuccess(): void
    {
        $payload = $this->getCustomerPayload('Delete Test');
        $iri = $this->createCustomer($payload);
        $client = self::createClient();
        $client->request('DELETE', $iri);
        $this->assertResponseStatusCodeSame(204);
        $client->request('GET', $iri);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteCustomerNotFound(): void
    {
        $client = self::createClient();
        $ulid = (string) $this->faker->ulid();
        $client->request('DELETE', "/api/customers/{$ulid}");
        $this->assertResponseStatusCodeSame(404);
    }

    private function assertSuccessfulCreation(
        array $payload,
        array $data
    ): void {
        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame(
            'content-type',
            'application/ld+json; charset=utf-8'
        );
        $this->assertArrayHasKey('@id', $data);
        $this->assertSame($payload['email'], $data['email']);
    }

    private function createCustomer(
        array $payload
    ): string {
        $client = self::createClient();
        $response = $client->request(
            'POST',
            '/api/customers',
            [
                'headers' => [
                    'Content-Type' => 'application/ld+json',
                ],
                'body' => json_encode($payload),
            ]
        );
        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();
        return (string) $data['@id'];
    }

    private function getCustomerPayload(
        string $name = 'Test Customer'
    ): array {
        return [
            'email' => $this->faker->unique()->email(),
            'phone' => '0123456789',
            'initials' => $name,
            'leadSource' => 'Google',
            'type' => $this->createCustomerType(),
            'status' => $this->createCustomerStatus(),
            'confirmed' => true,
        ];
    }

    private function createCustomerStatus(): string
    {
        $client = self::createClient();
        $payload = [
            'value' => 'Active',
        ];
        $response = $client->request(
            'POST',
            '/api/customer_statuses',
            [
                'headers' => [
                    'Content-Type' => 'application/ld+json',
                ],
                'body' => json_encode($payload),
            ]
        );
        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();
        $this->assertSame(
            'CustomerStatus',
            (string) $data['@type']
        );
        return (string) $data['@id'];
    }

    private function createCustomerType(): string
    {
        $client = self::createClient();
        $payload = [
            'value' => 'Prospect',
        ];
        $response = $client->request(
            'POST',
            '/api/customer_types',
            [
                'headers' => [
                    'Content-Type' => 'application/ld+json',
                ],
                'body' => json_encode($payload),
            ]
        );
        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();
        $this->assertSame(
            'CustomerType',
            (string) $data['@type']
        );
        return (string) $data['@id'];
    }
}
