<?php

declare(strict_types=1);

namespace App\Tests\Integration;

final class CustomerStatusApiTest extends BaseIntegrationTest
{
    public function testGetCustomerStatusesCollection(): void
    {
        $this->createCustomerStatus();
        $client = self::createClient();
        $response = $client->request('GET', '/api/customer_statuses');
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('member', $data);
        $this->assertIsArray($data['member']);
    }

    public function testGetCustomerStatusNotFound(): void
    {
        $ulid = (string) $this->faker->ulid();
        $client = self::createClient();
        $client->request('GET', "/api/customer_statuses/{$ulid}");
        $this->assertResponseStatusCodeSame(404);
    }

    public function testCreateCustomerStatusSuccess(): void
    {
        $payload = $this->getStatusPayload();
        $iri = $this->createEntity('/api/customer_statuses', $payload);
        $client = self::createClient();
        $response = $client->request('GET', $iri);
        $this->assertResponseStatusCodeSame(200);
        $data = $response->toArray();
        $this->assertArrayHasKey('@id', $data);
        $this->assertSame('Active', $data['value']);
    }

    public function testCreateCustomerStatusFailure(): void
    {
        $client = self::createClient();
        $client->request(
            'POST',
            '/api/customer_statuses',
            [
                'headers' => ['Content-Type' => 'application/ld+json'],
                'body' => json_encode([]),
            ]
        );
        $this->assertResponseStatusCodeSame(422);
    }

    public function testReplaceCustomerStatusSuccess(): void
    {
        $orig = $this->getStatusPayload();
        $iri = $this->createEntity('/api/customer_statuses', $orig);
        $upd = ['value' => 'Inactive'];
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
        $data = (self::createClient()->request('GET', $iri))->toArray();
        $this->assertSame('Inactive', $data['value']);
    }

    public function testReplaceCustomerStatusFailure(): void
    {
        $orig = $this->getStatusPayload('Active');
        $iri = $this->createEntity('/api/customer_statuses', $orig);
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

    public function testReplaceCustomerStatusNotFound(): void
    {
        $ulid = (string) $this->faker->ulid();
        $upd = ['value' => 'Inactive'];
        $client = self::createClient();
        $client->request(
            'PUT',
            "/api/customer_statuses/{$ulid}",
            [
                'headers' => ['Content-Type' => 'application/ld+json'],
                'body' => json_encode($upd),
            ]
        );
        $this->assertResponseStatusCodeSame(404);
    }

    public function testPatchCustomerStatusSuccess(): void
    {
        $orig = $this->getStatusPayload();
        $iri = $this->createEntity('/api/customer_statuses', $orig);
        $patch = ['value' => 'Pending'];
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
        $this->assertSame('Pending', $data['value']);
    }

    public function testPatchCustomerStatusFailure(): void
    {
        $orig = $this->getStatusPayload();
        $iri = $this->createEntity('/api/customer_statuses', $orig);
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

    public function testPatchCustomerStatusNotFound(): void
    {
        $ulid = (string) $this->faker->ulid();
        $patch = ['value' => 'Pending'];
        $client = self::createClient();
        $client->request(
            'PATCH',
            "/api/customer_statuses/{$ulid}",
            [
                'headers' => ['Content-Type' => 'application/merge-patch+json'],
                'body' => json_encode($patch),
            ]
        );
        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteCustomerStatusSuccess(): void
    {
        $orig = $this->getStatusPayload();
        $iri = $this->createEntity('/api/customer_statuses', $orig);
        $client = self::createClient();
        $client->request('DELETE', $iri);
        $this->assertResponseStatusCodeSame(204);
        $client->request('GET', $iri);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteCustomerStatusNotFound(): void
    {
        $ulid = (string) $this->faker->ulid();
        $client = self::createClient();
        $client->request(
            'DELETE',
            "/api/customer_statuses/{$ulid}"
        );
        $this->assertResponseStatusCodeSame(404);
    }

    /**
     * @return array<string, string>
     */
    private function getStatusPayload(string $value = 'Active'): array
    {
        return ['value' => $value];
    }

    private function createCustomerStatus(): string
    {
        return $this->createEntity(
            '/api/customer_statuses',
            $this->getStatusPayload()
        );
    }
}
