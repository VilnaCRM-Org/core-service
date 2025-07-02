<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;

final class CustomerApiTest extends BaseIntegrationTest
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

    public function testCreateCustomerWithoutInitials(): void
    {
        $payload = $this->getCustomer();
        unset($payload['initials']);

        $client = self::createClient();
        $client->request('POST', '/api/customers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'initials: This value should not be blank',
            $error['detail']
        );
    }

    public function testCreateCustomerWithTooLongInitials(): void
    {
        $payload = $this->getCustomer();
        $payload['initials'] = str_repeat('X', 256);

        $client = self::createClient();
        $client->request('POST', '/api/customers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'initials: This value is too long',
            $error['detail']
        );
    }

    public function testCreateCustomerWithInvalidEmail(): void
    {
        $payload = $this->getCustomer();
        $payload['email'] = 'not-an-email';

        $client = self::createClient();
        $client->request('POST', '/api/customers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'email: This value is not a valid email address.',
            $error['detail']
        );
    }

    public function testCreateCustomerWithTooLongEmail(): void
    {
        $payload = $this->getCustomer();
        $payload['email'] = str_repeat('a', 256) . '@example.com';

        $client = self::createClient();
        $client->request('POST', '/api/customers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'email: This value is too long',
            $error['detail']
        );
    }

    public function testCreateCustomerWithDuplicateEmail(): void
    {
        $payload = $this->getCustomer();
        $this->createEntity('/api/customers', $payload);

        $client = self::createClient();
        $client->request('POST', '/api/customers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString('email:', $error['detail']);
    }

    public function testCreateCustomerWithoutPhone(): void
    {
        $payload = $this->getCustomer();
        unset($payload['phone']);

        $client = self::createClient();
        $client->request('POST', '/api/customers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'phone: This value should not be blank',
            $error['detail']
        );
    }

    public function testCreateCustomerWithTooLongPhone(): void
    {
        $payload = $this->getCustomer();
        $payload['phone'] = str_repeat('9', 256);

        $client = self::createClient();
        $client->request('POST', '/api/customers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'phone: This value is too long',
            $error['detail']
        );
    }

    public function testCreateCustomerWithoutLeadSource(): void
    {
        $payload = $this->getCustomer();
        unset($payload['leadSource']);

        $client = self::createClient();
        $client->request('POST', '/api/customers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'leadSource: This value should not be blank',
            $error['detail']
        );
    }

    public function testCreateCustomerWithTooLongLeadSource(): void
    {
        $payload = $this->getCustomer();
        $payload['leadSource'] = str_repeat('L', 256);

        $client = self::createClient();
        $client->request('POST', '/api/customers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'leadSource: This value is too long',
            $error['detail']
        );
    }

    public function testCreateCustomerWithoutType(): void
    {
        $payload = $this->getCustomer();
        unset($payload['type']);

        $client = self::createClient();
        $client->request('POST', '/api/customers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'type: This value should not be blank.',
            $error['detail']
        );
    }

    public function testCreateCustomerWithoutStatus(): void
    {
        $payload = $this->getCustomer();
        unset($payload['status']);

        $client = self::createClient();
        $client->request('POST', '/api/customers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'status: This value should not be blank',
            $error['detail']
        );
    }

    public function testCreateCustomerWithNonBooleanConfirmed(): void
    {
        $payload = $this->getCustomer();
        $payload['confirmed'] = 'yes';

        $client = self::createClient();
        $client->request('POST', '/api/customers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(400);
        $this->assertStringContainsString(
            'The input data is misformatted.',
            $error['detail']
        );
    }

    public function testPatchCustomerWithTooLongInitials(): void
    {
        $iri = $this->createEntity('/api/customers', $this->getCustomer());
        $patch = ['initials' => str_repeat('X', 256)];

        $client = self::createClient();
        $client->request('PATCH', $iri, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'body' => json_encode($patch),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'initials: This value is too long',
            $error['detail']
        );
    }

    public function testPatchCustomerWithInvalidEmail(): void
    {
        $iri = $this->createEntity('/api/customers', $this->getCustomer());
        $patch = ['email' => 'bad@'];

        $client = self::createClient();
        $client->request('PATCH', $iri, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'body' => json_encode($patch),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'email: This value is not a valid email address.',
            $error['detail']
        );
    }

    public function testPatchCustomerWithTooLongEmail(): void
    {
        $iri = $this->createEntity('/api/customers', $this->getCustomer());
        $patch = ['email' => str_repeat('a', 256) . '@x.com'];

        $client = self::createClient();
        $client->request('PATCH', $iri, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'body' => json_encode($patch),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'email: This value is too long',
            $error['detail']
        );
    }

    public function testPatchCustomerWithDuplicateEmail(): void
    {
        $existing = $this->getCustomer();
        $this->createEntity('/api/customers', $existing);

        $iri = $this->createEntity('/api/customers', $this->getCustomer());
        $patch = ['email' => $existing['email']];

        $client = self::createClient();
        $client->request('PATCH', $iri, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'body' => json_encode($patch),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString('email:', $error['detail']);
    }

    public function testPatchCustomerWithTooLongPhone(): void
    {
        $iri = $this->createEntity('/api/customers', $this->getCustomer());
        $patch = ['phone' => str_repeat('9', 256)];

        $client = self::createClient();
        $client->request('PATCH', $iri, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'body' => json_encode($patch),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'phone: This value is too long',
            $error['detail']
        );
    }

    public function testPatchCustomerWithTooLongLeadSource(): void
    {
        $iri = $this->createEntity('/api/customers', $this->getCustomer());
        $patch = ['leadSource' => str_repeat('L', 256)];

        $client = self::createClient();
        $client->request('PATCH', $iri, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'body' => json_encode($patch),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'leadSource: This value is too long',
            $error['detail']
        );
    }

    public function testPatchCustomerWithNonBooleanConfirmed(): void
    {
        $iri = $this->createEntity('/api/customers', $this->getCustomer());
        $patch = ['confirmed' => 'maybe'];

        $client = self::createClient();
        $client->request('PATCH', $iri, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'body' => json_encode($patch),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(400);
        $this->assertStringContainsString(
            'The input data is misformatted.',
            $error['detail']
        );
    }

    public function testReplaceCustomerWithoutInitials(): void
    {
        $payload = $this->getCustomer();
        $iri = $this->createEntity('/api/customers', $payload);
        unset($payload['initials']);

        $client = self::createClient();
        $client->request('PUT', $iri, [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'initials: This value should not be blank',
            $error['detail']
        );
    }

    public function testReplaceCustomerWithTooLongInitials(): void
    {
        $payload = $this->getCustomer();
        $payload['initials'] = str_repeat('X', 256);
        $iri = $this->createEntity('/api/customers', $this->getCustomer());

        $client = self::createClient();
        $client->request('PUT', $iri, [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'initials: This value is too long',
            $error['detail']
        );
    }

    public function testReplaceCustomerWithoutEmail(): void
    {
        $payload = $this->getCustomer();
        unset($payload['email']);
        $iri = $this->createEntity('/api/customers', $this->getCustomer());

        $client = self::createClient();
        $client->request('PUT', $iri, [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'email: This value should not be blank',
            $error['detail']
        );
    }

    public function testReplaceCustomerWithInvalidEmailFormat(): void
    {
        $payload = $this->getCustomer();
        $payload['email'] = 'wrong';
        $iri = $this->createEntity('/api/customers', $this->getCustomer());

        $client = self::createClient();
        $client->request('PUT', $iri, [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'email: This value is not a valid email address.',
            $error['detail']
        );
    }

    public function testReplaceCustomerWithTooLongEmail(): void
    {
        $payload = $this->getCustomer();
        $payload['email'] = str_repeat('a', 256) . '@x.com';
        $iri = $this->createEntity('/api/customers', $this->getCustomer());

        $client = self::createClient();
        $client->request('PUT', $iri, [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'email: This value is too long',
            $error['detail']
        );
    }

    public function testReplaceCustomerWithoutPhone(): void
    {
        $payload = $this->getCustomer();
        unset($payload['phone']);
        $iri = $this->createEntity('/api/customers', $this->getCustomer());

        $client = self::createClient();
        $client->request('PUT', $iri, [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'phone: This value should not be blank',
            $error['detail']
        );
    }

    public function testReplaceCustomerWithTooLongPhone(): void
    {
        $payload = $this->getCustomer();
        $payload['phone'] = str_repeat('9', 256);
        $iri = $this->createEntity('/api/customers', $this->getCustomer());

        $client = self::createClient();
        $client->request('PUT', $iri, [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'phone: This value is too long',
            $error['detail']
        );
    }

    public function testReplaceCustomerWithoutLeadSource(): void
    {
        $payload = $this->getCustomer();
        unset($payload['leadSource']);
        $iri = $this->createEntity('/api/customers', $this->getCustomer());

        $client = self::createClient();
        $client->request('PUT', $iri, [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'leadSource: This value should not be blank',
            $error['detail']
        );
    }

    public function testReplaceCustomerWithTooLongLeadSource(): void
    {
        $payload = $this->getCustomer();
        $payload['leadSource'] = str_repeat('L', 256);
        $iri = $this->createEntity('/api/customers', $this->getCustomer());

        $client = self::createClient();
        $client->request('PUT', $iri, [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'leadSource: This value is too long',
            $error['detail']
        );
    }

    public function testReplaceCustomerWithoutType(): void
    {
        $payload = $this->getCustomer();
        unset($payload['type']);
        $iri = $this->createEntity('/api/customers', $this->getCustomer());

        $client = self::createClient();
        $client->request('PUT', $iri, [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'type: This value should not be blank.',
            $error['detail']
        );
    }

    public function testReplaceCustomerWithoutStatus(): void
    {
        $payload = $this->getCustomer();
        unset($payload['status']);
        $iri = $this->createEntity('/api/customers', $this->getCustomer());

        $client = self::createClient();
        $client->request('PUT', $iri, [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'status: This value should not be blank',
            $error['detail']
        );
    }

    public function testReplaceCustomerWithNonBooleanConfirmed(): void
    {
        $payload = $this->getCustomer();
        $payload['confirmed'] = 'nope';
        $iri = $this->createEntity('/api/customers', $this->getCustomer());

        $client = self::createClient();
        $client->request('PUT', $iri, [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(400);
        $this->assertStringContainsString(
            'The input data is misformatted.',
            $error['detail']
        );
    }

    public function validationForBlankEmail(Client $client): void
    {
        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'email: This value should not be blank',
            $error['detail']
        );
    }

    public function validationNotFound(Client $client): void
    {
        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(404);
        $this->assertStringContainsString('Not Found', $error['detail']);
    }

    /**
     * @return (false|string)[]
     *
     * @psalm-return array{email: string, phone: string, initials: string, leadSource: string, type: string, status: string, confirmed: false}
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

    /**
     * @return (string|true)[]
     *
     * @psalm-return array{email: string, phone: string, initials: string, leadSource: string, type: string, status: string, confirmed: true}
     */
    private function getCustomer(string $name = 'Test Customer'): array
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
