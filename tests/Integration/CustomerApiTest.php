<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;

final class CustomerApiTest extends BaseApiCase
{
    public function testCreateCustomerSuccess(): void
    {
        $payload = $this->getCustomer('John Doe');
        $iri = $this->createEntity('/api/customers', $payload);
        $client = self::createClient();
        $response = $client->request('GET', $iri);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertSame($payload['email'], $data['email']);
    }

    public function testPostCustomerSuccess(): void
    {
        $payload = $this->getCustomer('Jane Doe');

        $responseData = $this->jsonRequest('POST', '/api/customers', $payload);

        $this->assertResponseStatusCodeSame(201);

        $this->assertResponseHeaderSame(
            'content-type',
            'application/ld+json; charset=utf-8'
        );

        $this->assertArrayHasKey('@id', $responseData);

        $this->assertSame($payload['email'], $responseData['email']);
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
        $this->validationForBlankEmail($client);
    }

    public function testCreateCustomerWithUnknownAbsoluteStatusIriReturnsNotFound(): void
    {
        $payload = $this->getCustomer('Unknown Status');
        $payload['status'] = 'https://W.q250kA9Gmy.p.aETna';

        $client = self::createClient();
        $client->request(
            'POST',
            '/api/customers',
            [
                'headers' => ['Content-Type' => 'application/ld+json'],
                'body' => json_encode($payload),
            ]
        );

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(404);
        $this->assertStringContainsString('Customer status', $error['detail']);
    }

    public function testGetCustomerSuccess(): void
    {
        $payload = $this->getCustomer('Test Get');
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
        $this->validationNotFound($client);
    }

    public function testReplaceCustomerSuccess(): void
    {
        $payload = $this->getCustomer('Replace Test');
        $iri = $this->createEntity('/api/customers', $payload);

        $updatedPayload = $this->getUpdatedCustomerPayload();
        $this->updateCustomer($iri, $updatedPayload);
        $this->verifyCustomerUpdate($iri, $updatedPayload);
    }

    public function testReplaceCustomerWithExistingOwnEmail(): void
    {
        $payload = $this->getCustomer('Replace Same Email');
        $iri = $this->createEntity('/api/customers', $payload);
        $updatedPayload = $this->getUpdatedCustomerPayload();
        $updatedPayload['email'] = $payload['email'];

        $this->updateCustomer($iri, $updatedPayload);
        $this->verifyCustomerUpdate($iri, $updatedPayload);
    }

    public function testReplaceCustomerFailure(): void
    {
        $payload = $this->getCustomer('Missing Email');
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
        $this->validationForBlankEmail($client);
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
        $this->validationNotFound($client);
    }

    public function testPatchCustomerSuccess(): void
    {
        $payload = $this->getCustomer('Patch Test');
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

    public function testPatchCustomerWithExistingOwnEmail(): void
    {
        $payload = $this->getCustomer('Patch Same Email');
        $iri = $this->createEntity('/api/customers', $payload);
        $patch = ['email' => $payload['email']];
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
        $this->assertSame($payload['email'], $data['email']);
    }

    public function testPatchCustomerFailure(): void
    {
        $payload = $this->getCustomer('Patch Fail');
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
        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'email: This value is not a valid email address',
            $error['detail']
        );
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
        $this->validationNotFound($client);
    }

    public function testDeleteCustomerSuccess(): void
    {
        $payload = $this->getCustomer('Delete Test');
        $iri = $this->createEntity('/api/customers', $payload);
        $client = self::createClient();
        $client->request('DELETE', $iri);
        $this->assertResponseStatusCodeSame(204);
        $client->request('GET', $iri);
        $this->validationNotFound($client);
    }

    public function testDeleteCustomerNotFound(): void
    {
        $ulid = (string) $this->faker->ulid();
        $client = self::createClient();
        $client->request('DELETE', "/api/customers/{$ulid}");
        $this->validationNotFound($client);
    }

    /**
     * @return array<string, string|bool|CustomerType|CustomerStatus>
     */
    private function getUpdatedCustomerPayload(): array
    {
        return [
            'email' => $this->faker->unique()->email(),
            'phone' => $this->faker->phoneNumber(),
            'initials' => $this->faker->word(),
            'leadSource' => $this->faker->word(),
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

    private function validationForBlankEmail(Client $client): void
    {
        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'email: This value should not be blank',
            $error['detail']
        );
    }

    private function validationNotFound(Client $client): void
    {
        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(404);
        $this->assertStringContainsString('Not Found', $error['detail']);
    }

    /**
     * @return array<string, string|bool>
     */
    private function getCustomer(string $initials = 'Test Customer'): array
    {
        return [
            'email' => $this->faker->unique()->email(),
            'phone' => $this->faker->phoneNumber(),
            'initials' => $initials,
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
