<?php

declare(strict_types=1);

namespace App\Tests\Integration;

final class CustomerTypeApiTest extends BaseTest
{
    public function testCreateCustomerTypeWithExtraFields(): void
    {
        $value = $this->faker->word();
        $payload = array_merge(
            $this->getTypePayload($value),
            ['unexpected' => 'value']
        );
        $iri = $this->createEntity('/api/customer_types', $payload);
        $client = self::createClient();
        $response = $client->request('GET', $iri);
        $data = $response->toArray();
        $this->assertResponseStatusCodeSame(200);
        $this->assertArrayHasKey('@id', $data);
        $this->assertSame($value, $data['value']);
        $this->assertArrayNotHasKey(
            'unexpected',
            $data,
            'Unexpected field should not be persisted or returned'
        );
    }

    public function testCreateCustomerTypeWithInvalidJson(): void
    {
        $client = self::createClient();
        $client->request(
            'POST',
            '/api/customer_types',
            [
                'headers' => ['Content-Type' => 'application/ld+json'],
                'body' => '{invalid json}',
            ]
        );
        $this->assertResponseStatusCodeSame(400);
    }

    public function testResponseHeadersOnCreate(): void
    {
        $value = $this->faker->word();
        $payload = $this->getTypePayload($value);
        $iri = $this->createEntity('/api/customer_types', $payload);
        $client = self::createClient();
        $client->request('GET', $iri);
        self::assertResponseHeaderSame(
            'Content-Type',
            'application/ld+json; charset=utf-8'
        );
    }

    public function testGetCustomerTypeResponseStructure(): void
    {
        $value = $this->faker->word();
        $payload = $this->getTypePayload($value);
        $iri = $this->createEntity('/api/customer_types', $payload);
        $client = self::createClient();
        $response = $client->request('GET', $iri);
        $data = $response->toArray();

        $this->testGetCustomerTypeResponseAssertion($data, $value);
    }

    public function testPatchCustomerTypeWithExtraFields(): void
    {
        $orig = $this->getTypePayload('Retail');
        $iri = $this->createEntity('/api/customer_types', $orig);

        $patch = ['value' => 'VIP', 'unknown' => 'unexpected'];
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
        $this->assertArrayNotHasKey('unknown', $data, 'Extra field is ignored');
    }

    public function testCreateCustomerTypeWithInvalidContentType(): void
    {
        $value = $this->faker->word();
        $payload = $this->getTypePayload($value);
        $client = self::createClient();
        $client->request(
            'POST',
            '/api/customer_types',
            [
                'headers' => ['Content-Type' => 'application/json'],
                'body' => json_encode($payload),
            ]
        );
        $this->assertResponseStatusCodeSame(415);
    }

    public function testDeleteOnCustomerTypesCollectionNotAllowed(): void
    {
        $client = self::createClient();
        $client->request('DELETE', '/api/customer_types');
        $this->assertResponseStatusCodeSame(405);
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
        $ulid = (string) $this->faker->ulid();
        $client = self::createClient();
        $client->request('GET', "/api/customer_types/{$ulid}");
        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(404);
        $this->assertStringContainsString('Not Found', $error['detail']);
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
        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'value: This value should not be blank',
            $error['detail']
        );
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
        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'value: This value should not be blank',
            $error['detail']
        );
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
        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(404);
        $this->assertStringContainsString('Not Found', $error['detail']);
    }

    public function testReplaceCustomerTypeFailureWithValidation(): void
    {
        $orig = $this->getTypePayload('Retail');
        $iri = $this->createEntity('/api/customer_types', $orig);
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
        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(404);
        $this->assertStringContainsString('Not Found', $error['detail']);
    }

    public function testDeleteCustomerTypeSuccess(): void
    {
        $orig = $this->getTypePayload('Retail');
        $iri = $this->createEntity('/api/customer_types', $orig);
        $client = self::createClient();
        $client->request('DELETE', $iri);
        $this->assertResponseStatusCodeSame(204);
        $client->request('GET', $iri);
        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(404);
        $this->assertStringContainsString('Not Found', $error['detail']);
    }

    public function testDeleteCustomerTypeNotFound(): void
    {
        $ulid = (string) $this->faker->ulid();
        $client = self::createClient();
        $client->request('DELETE', "/api/customer_types/{$ulid}");
        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(404);
        $this->assertStringContainsString('Not Found', $error['detail']);
    }

    /**
     * @return array<string, string>
     */
    private function getTypePayload(string $value = 'Prospect'): array
    {
        return ['value' => $value];
    }

    private function createCustomerType(): void
    {
        $this->createEntity(
            '/api/customer_types',
            $this->getTypePayload()
        );
    }

    /**
     * @param array<string, string> $data
     */
    private function testGetCustomerTypeResponseAssertion(
        array $data,
        string $value
    ): void {
        $this->assertArrayHasKey(
            '@context',
            $data,
            'Missing @context property'
        );
        $this->assertArrayHasKey('@id', $data, 'Missing @id property');
        $this->assertArrayHasKey('@type', $data, 'Missing @type property');
        $this->assertArrayHasKey(
            'value',
            $data,
            'Missing expected "value" property'
        );
        $this->assertSame(
            $value,
            $data['value'],
            'The value returned does not match the payload'
        );
    }
}
