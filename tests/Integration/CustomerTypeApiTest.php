<?php

declare(strict_types=1);

namespace App\Tests\Integration;

final class CustomerTypeApiTest extends BaseIntegrationTest
{
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
        $ulid = (string) $this->faker->ulid();
        $client = self::createClient();
        $client->request('GET', "/api/customer_types/{$ulid}");
        $this->assertResponseStatusCodeSame(404);
    }

    public function testCreateCustomerTypeSuccess(): void
    {
        $value = $this->faker->word();
        $payload = $this->getTypePayload($value);
        $iri = $this->createEntity('/api/customer_types', $payload);
        $client = self::createClient();
        $response = $client->request('GET', $iri);
        $this->assertResponseStatusCodeSame(200);
        $data = $response->toArray();
        $this->assertArrayHasKey('@id', $data);
        $this->assertSame($value, $data['value']);
    }

    public function testCreateCustomerTypeFailure(): void
    {
        $client = self::createClient();
        $client->request(
            'POST',
            '/api/customer_types',
            [
                'headers' => ['Content-Type' => 'application/ld+json'],
                'body' => json_encode([]),
            ]
        );
        $this->assertResponseStatusCodeSame(422);
    }

    public function testReplaceCustomerTypeSuccess(): void
    {
        $orig = $this->getTypePayload('Retail');
        $iri = $this->createEntity('/api/customer_types', $orig);
        $upd = ['value' => 'Wholesale'];
        $client = self::createClient();
        $client->request(
            'PUT',
            $iri,
            [
                'headers' => ['Content-Type' => 'application/ld+json'],
                'body' => json_encode($upd),
            ]
        );
        $this->assertResponseIsSuccessful();
        $response = $client->request('GET', $iri);
        $data = $response->toArray();
        $this->assertSame('Wholesale', $data['value']);
    }

    public function testReplaceCustomerTypeFailure(): void
    {
        $orig = $this->getTypePayload('Retail');
        $iri = $this->createEntity('/api/customer_types', $orig);
        $client = self::createClient();
        $client->request(
            'PUT',
            $iri,
            [
                'headers' => ['Content-Type' => 'application/ld+json'],
                'body' => json_encode([]),
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
                'headers' => ['Content-Type' => 'application/ld+json'],
                'body' => json_encode($upd),
            ]
        );
        $this->assertResponseStatusCodeSame(404);
    }

    public function testPatchCustomerTypeSuccess(): void
    {
        $orig = $this->getTypePayload('Retail');
        $iri = $this->createEntity('/api/customer_types', $orig);
        $patch = ['value' => 'VIP'];
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
        $response = $client->request('GET', $iri);
        $data = $response->toArray();
        $this->assertSame('VIP', $data['value']);
    }

    public function testPatchCustomerTypeFailure(): void
    {
        $orig = $this->getTypePayload('Retail');
        $iri = $this->createEntity('/api/customer_types', $orig);
        $patch = ['value' => ''];
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

    public function testPatchCustomerTypeNotFound(): void
    {
        $ulid = (string) $this->faker->ulid();
        $patch = ['value' => 'VIP'];
        $client = self::createClient();
        $client->request(
            'PATCH',
            "/api/customer_types/{$ulid}",
            [
                'headers' => ['Content-Type' => 'application/merge-patch+json'],
                'body' => json_encode($patch),
            ]
        );
        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteCustomerTypeSuccess(): void
    {
        $orig = $this->getTypePayload('Retail');
        $iri = $this->createEntity('/api/customer_types', $orig);
        $client = self::createClient();
        $client->request('DELETE', $iri);
        $this->assertResponseStatusCodeSame(204);
        $client->request('GET', $iri);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteCustomerTypeNotFound(): void
    {
        $ulid = (string) $this->faker->ulid();
        $client = self::createClient();
        $client->request('DELETE', "/api/customer_types/{$ulid}");
        $this->assertResponseStatusCodeSame(404);
    }

    /**
     * @return array<string, string>
     */
    private function getTypePayload(string $value = 'Prospect'): array
    {
        return ['value' => $value];
    }

    private function createCustomerType(): string
    {
        return $this->createEntity(
            '/api/customer_types',
            $this->getTypePayload()
        );
    }
}
