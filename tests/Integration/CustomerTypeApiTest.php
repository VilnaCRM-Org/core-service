<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Unit\UlidProvider;
use Faker\Factory;
use Faker\Generator;

final class CustomerTypeApiTest extends ApiTestCase
{
    private Generator $faker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->faker = Factory::create();
        $this->faker->addProvider(new UlidProvider($this->faker));
    }

    public function testGetCustomerTypesCollection(): void
    {
        $this->createCustomerType();
        $client = self::createClient();
        $response = $client->request('GET', '/api/customer_types');
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);
        $this->assertIsArray($data['member']);
    }

    public function testGetCustomerTypeNotFound(): void
    {
        $client = self::createClient();
        $ulid = (string) $this->faker->ulid();
        $client->request(
            'GET',
            "/api/customer_types/{$ulid}"
        );
        $this->assertResponseStatusCodeSame(404);
    }

    public function testCreateCustomerTypeSuccess(): void
    {
        $payload = $this->getTypePayload('Retail');
        $client = self::createClient();
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
        $this->assertArrayHasKey('@id', $data);
        $this->assertSame('Retail', $data['value']);
    }

    public function testCreateCustomerTypeFailure(): void
    {
        $payload = [];
        $client = self::createClient();
        $client->request(
            'POST',
            '/api/customer_types',
            [
                'headers' => [
                    'Content-Type' => 'application/ld+json',
                ],
                'body' => json_encode($payload),
            ]
        );
        $this->assertResponseStatusCodeSame(422);
    }

    public function testReplaceCustomerTypeSuccess(): void
    {
        $orig = $this->getTypePayload('Retail');
        $iri = $this->createCustomerType($orig);
        $upd = ['value' => 'Wholesale'];
        $client = self::createClient();
        $response = $client->request(
            'PUT',
            $iri,
            [
                'headers' => [
                    'Content-Type' => 'application/ld+json',
                ],
                'body' => json_encode($upd),
            ]
        );
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertSame('Wholesale', $data['value']);
    }

    public function testReplaceCustomerTypeFailure(): void
    {
        $orig = $this->getTypePayload('Retail');
        $iri = $this->createCustomerType($orig);
        $upd = [];
        $client = self::createClient();
        $client->request(
            'PUT',
            $iri,
            [
                'headers' => [
                    'Content-Type' => 'application/ld+json',
                ],
                'body' => json_encode($upd),
            ]
        );
        $this->assertResponseStatusCodeSame(422);
    }

    public function testReplaceCustomerTypeNotFound(): void
    {
        $ulid = (string) $this->faker->ulid();
        $upd = ['value' => 'Wholesale'];
        $client = self::createClient();
        $client->request(
            'PUT',
            "/api/customer_types/{$ulid}",
            [
                'headers' => [
                    'Content-Type' => 'application/ld+json',
                ],
                'body' => json_encode($upd),
            ]
        );
        $this->assertResponseStatusCodeSame(404);
    }

    public function testPatchCustomerTypeSuccess(): void
    {
        $orig = $this->getTypePayload('Retail');
        $iri = $this->createCustomerType($orig);
        $patch = ['value' => 'VIP'];
        $client = self::createClient();
        $response = $client->request(
            'PATCH',
            $iri,
            [
                'headers' => [
                    'Content-Type' => 'application/merge-patch+json',
                ],
                'body' => json_encode($patch),
            ]
        );
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertSame('VIP', $data['value']);
    }

    public function testPatchCustomerTypeFailure(): void
    {
        $orig = $this->getTypePayload('Retail');
        $iri = $this->createCustomerType($orig);
        $patch = ['value' => ''];
        $client = self::createClient();
        $client->request(
            'PATCH',
            $iri,
            [
                'headers' => [
                    'Content-Type' => 'application/merge-patch+json',
                ],
                'body' => json_encode($patch),
            ]
        );
        $this->assertResponseStatusCodeSame(422);
    }

    public function testPatchCustomerTypeNotFound(): void
    {
        $client = self::createClient();
        $ulid = (string) $this->faker->ulid();
        $patch = ['value' => 'VIP'];
        $client->request(
            'PATCH',
            "/api/customer_types/{$ulid}",
            [
                'headers' => [
                    'Content-Type' => 'application/merge-patch+json',
                ],
                'body' => json_encode($patch),
            ]
        );
        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteCustomerTypeSuccess(): void
    {
        $orig = $this->getTypePayload('Retail');
        $iri = $this->createCustomerType($orig);
        $client = self::createClient();
        $client->request('DELETE', $iri);
        $this->assertResponseStatusCodeSame(204);
        $client->request('GET', $iri);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteCustomerTypeNotFound(): void
    {
        $client = self::createClient();
        $ulid = (string) $this->faker->ulid();
        $client->request(
            'DELETE',
            "/api/customer_types/{$ulid}"
        );
        $this->assertResponseStatusCodeSame(404);
    }

    private function getTypePayload(string $value = 'Prospect'): array
    {
        return [
            'value' => $value,
        ];
    }

    private function createCustomerType(
        ?array $payload = null
    ): string {
        $payload = $payload ?? $this->getTypePayload();
        $client = self::createClient();
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
        return (string) $data['@id'];
    }
}
