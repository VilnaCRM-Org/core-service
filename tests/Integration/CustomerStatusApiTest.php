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
        $this->assertArrayHasKey('totalItems', $data);
    }

    public function testGetCustomerStatusesWithUnsupportedQueryParameter(): void
    {
        $client = self::createClient();
        $response = $client->request(
            'GET',
            '/api/customer_statuses?unsupportedParam=value'
        );
        $this->assertResponseIsSuccessful();
        $this->assertArrayHasKey('member', $response->toArray());
    }

    public function testGetCustomerStatusesCollectionWithPagination(): void
    {
        $client = self::createClient();
        $response = $client->request(
            'GET',
            '/api/customer_statuses?page=2&itemsPerPage=10'
        );
        $data = $response->toArray();
        $this->assertResponseIsSuccessful();
        $this->assertArrayHasKey('member', $data);
        $this->assertArrayHasKey('totalItems', $data);
        $this->assertLessThanOrEqual(10, count($data['member']));
    }

    public function testGetCustomerStatusesCollectionWithOrdering(): void
    {
        $client = self::createClient();
        $response = $client->request(
            'GET',
            '/api/customer_statuses?order[ulid]=asc&order[value]=desc'
        );
        $data = $response->toArray();
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString(
            'order%5Bulid%5D=asc',
            $data['view']['@id'] ?? ''
        );
        $this->assertStringContainsString(
            'order%5Bvalue%5D=desc',
            $data['view']['@id'] ?? ''
        );
    }

    public function testGetCustomerStatusesCollectionFilteringByValue(): void
    {
        $this->createEntity('/api/customer_statuses', ['value' => 'Active']);
        $client = self::createClient();
        $response = $client->request(
            'GET',
            '/api/customer_statuses?value=Active'
        );
        $data = $response->toArray();
        $this->assertResponseIsSuccessful();
        $this->assertNotEmpty($data['member']);
        $this->assertStringContainsString(
            'Active',
            $data['member'][0]['value']
        );
    }

    public function testGetCustomerStatusNotFound(): void
    {
        $ulid = (string) $this->faker->ulid();
        $client = self::createClient();
        $client->request('GET', "/api/customer_statuses/{$ulid}");
        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame(
            'content-type',
            'application/problem+json; charset=utf-8'
        );
        $error = $client->getResponse()->toArray(false);
        $this->assertStringContainsString('Not Found', $error['detail']);
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
        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'value: This value should not be blank',
            $error['detail']
        );
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
        $orig = $this->getStatusPayload();
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
        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'value: This value should not be blank',
            $error['detail']
        );
    }

    public function testReplaceCustomerStatusFailureWithValidation(): void
    {
        $orig = $this->getStatusPayload();
        $iri = $this->createEntity('/api/customer_statuses', $orig);
        $client = self::createClient();
        $client->request(
            'PUT',
            $iri,
            [
                'headers' => ['Content-Type' => 'application/ld+json'],
                'body' => json_encode(['value' => '']),
            ]
        );
        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'value: This value should not be blank',
            $error['detail']
        );
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
        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(404);
        $this->assertStringContainsString('Not Found', $error['detail']);
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
        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(404);
        $this->assertStringContainsString('Not Found', $error['detail']);
    }

    public function testDeleteCustomerStatusSuccess(): void
    {
        $orig = $this->getStatusPayload();
        $iri = $this->createEntity('/api/customer_statuses', $orig);
        $client = self::createClient();
        $client->request('DELETE', $iri);
        $this->assertResponseStatusCodeSame(204);
        $client->request('GET', $iri);
        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(404);
        $this->assertStringContainsString('Not Found', $error['detail']);
    }

    public function testDeleteCustomerStatusNotFound(): void
    {
        $ulid = (string) $this->faker->ulid();
        $client = self::createClient();
        $client->request(
            'DELETE',
            "/api/customer_statuses/{$ulid}"
        );
        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(404);
        $this->assertStringContainsString('Not Found', $error['detail']);
    }

    /**
     * @return array<string>
     *
     * @psalm-return array{value: string}
     */
    private function getStatusPayload(string $value = 'Active'): array
    {
        return ['value' => $value];
    }

    /**
     * @psalm-suppress UnusedReturnValue
     */
    private function createCustomerStatus(): string
    {
        return $this->createEntity(
            '/api/customer_statuses',
            $this->getStatusPayload()
        );
    }
}
