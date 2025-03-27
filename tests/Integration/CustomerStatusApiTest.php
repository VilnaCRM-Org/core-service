<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Unit\UlidProvider;
use Faker\Factory;
use Faker\Generator;

final class CustomerStatusApiTest extends ApiTestCase
{
    private Generator $faker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->faker = Factory::create();
        $this->faker->addProvider(new UlidProvider($this->faker));
    }

    public function testGetCustomerStatusesCollection(): void
    {
        $this->createCustomerStatus();
        $client = self::createClient();
        $response = $client->request(
            'GET',
            '/api/customer_statuses'
        );
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);
        $this->assertIsArray($data['member']);
    }

    public function testGetCustomerStatusNotFound(): void
    {
        $client = self::createClient();
        $ulid = (string) $this->faker->ulid();
        $client->request(
            'GET',
            "/api/customer_statuses/{$ulid}"
        );
        $this->assertResponseStatusCodeSame(404);
    }

    public function testCreateCustomerStatusSuccess(): void
    {
        $payload = $this->getStatusPayload();
        $client = self::createClient();
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
        $this->assertArrayHasKey('@id', $data);
        $this->assertSame('Active', $data['value']);
    }

    public function testCreateCustomerStatusFailure(): void
    {
        $payload = [];
        $client = self::createClient();
        $client->request(
            'POST',
            '/api/customer_statuses',
            [
                'headers' => [
                    'Content-Type' => 'application/ld+json',
                ],
                'body' => json_encode($payload),
            ]
        );
        $this->assertResponseStatusCodeSame(422);
    }

    public function testReplaceCustomerStatusSuccess(): void
    {
        $orig = $this->getStatusPayload();
        $iri = $this->createCustomerStatus($orig);
        $upd = ['value' => 'Inactive',];
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
        $this->assertSame('Inactive', $data['value']);
    }

    public function testReplaceCustomerStatusFailure(): void
    {
        $orig = $this->getStatusPayload('Active');
        $iri = $this->createCustomerStatus($orig);
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

    public function testReplaceCustomerStatusNotFound(): void
    {
        $ulid = (string) $this->faker->ulid();
        $upd = ['value' => 'Inactive',];
        $client = self::createClient();
        $client->request(
            'PUT',
            "/api/customer_statuses/{$ulid}",
            [
                'headers' => [
                    'Content-Type' => 'application/ld+json',
                ],
                'body' => json_encode($upd),
            ]
        );
        $this->assertResponseStatusCodeSame(404);
    }

    public function testPatchCustomerStatusSuccess(): void
    {
        $orig = $this->getStatusPayload();
        $iri = $this->createCustomerStatus($orig);
        $patch = ['value' => 'Pending',];
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
        $this->assertSame('Pending', $data['value']);
    }

    public function testPatchCustomerStatusFailure(): void
    {
        $orig = $this->getStatusPayload();
        $iri = $this->createCustomerStatus($orig);
        $patch = ['value' => '',];
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

    public function testPatchCustomerStatusNotFound(): void
    {
        $client = self::createClient();
        $ulid = (string) $this->faker->ulid();
        $patch = ['value' => 'Pending',];
        $client->request(
            'PATCH',
            "/api/customer_statuses/{$ulid}",
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

    public function testDeleteCustomerStatusSuccess(): void
    {
        $orig = $this->getStatusPayload();
        $iri = $this->createCustomerStatus($orig);
        $client = self::createClient();
        $client->request('DELETE', $iri);
        $this->assertResponseStatusCodeSame(204);
        $client->request('GET', $iri);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteCustomerStatusNotFound(): void
    {
        $client = self::createClient();
        $ulid = (string) $this->faker->ulid();
        $client->request(
            'DELETE',
            "/api/customer_statuses/{$ulid}"
        );
        $this->assertResponseStatusCodeSame(404);
    }

    private function getStatusPayload(
        string $value = 'Active'
    ): array {
        return [
            'value' => $value,
        ];
    }

    private function createCustomerStatus(
        array $payload = null
    ): string {
        $payload = $payload ?? $this->getStatusPayload();
        $client = self::createClient();
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
        return (string) $data['@id'];
    }
}
