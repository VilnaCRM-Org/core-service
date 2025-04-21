<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Customer\Domain\Entity\CustomerStatus;
use App\Customer\Domain\Entity\CustomerType;

final class CustomerApiTest extends BaseIntegrationTest
{
    private function extractUlid(string $iri): string
    {
        return substr($iri, strrpos($iri, '/') + 1);
    }

    public function testGetCustomersCollection(): void
    {
        $client = self::createClient();
        $response = $client->request('GET', '/api/customers');
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertArrayHasKey('member', $data);
        $this->assertIsArray($data['member']);
    }

    public function testGetCustomersCollectionInvalidQuery(): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/customers', [
            'query' => ['page' => 'invalid'],
        ]);
        $this->assertResponseStatusCodeSame(400);
    }

    public function testEmptyCustomersCollection(): void
    {
        $client = self::createClient();
        $response = $client->request('GET', '/api/customers');
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertCount(0, $data['member']);
    }

    public function testFilterByInitials(): void
    {
        $payloadA = $this->getCustomerPayload('JD');
        $this->createEntity('/api/customers', $payloadA);
        $payloadB = $this->getCustomerPayload('FJ');
        $this->createEntity('/api/customers', $payloadB);

        $client = self::createClient();
        $response = $client->request('GET', '/api/customers', [
            'query' => ['initials' => 'JD'],
        ]);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertSame('JD', $data['member'][0]['initials']);
    }

    public function testFilterByInitialsArray(): void
    {
        $payloadA = $this->getCustomerPayload('AB');
        $this->createEntity('/api/customers', $payloadA);
        $payloadB = $this->getCustomerPayload('CD');
        $this->createEntity('/api/customers', $payloadB);
        $payloadC = $this->getCustomerPayload('DC');
        $this->createEntity('/api/customers', $payloadC);

        $client = self::createClient();
        $response = $client->request('GET', '/api/customers', [
            'query' => [
                'initials[]' => ['AB', 'CD'],
            ],
        ]);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertContains($data['member'][0]['initials'], ['AB', 'CD']);
        $this->assertContains($data['member'][1]['initials'], ['AB', 'CD']);
    }

    public function testFilterByEmail(): void
    {
        $this->createEntity('/api/customers', array_merge($this->getCustomerPayload(), ['email' => 'john.doe@example.com']));
        $this->createEntity('/api/customers', array_merge($this->getCustomerPayload(), ['email' => 'jane.doe@example.com']));

        $client = self::createClient();
        $response = $client->request('GET', '/api/customers', [
            'query' => ['email' => 'john.doe@example.com'],
        ]);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertSame('john.doe@example.com', $data['member'][0]['email']);
    }

    public function testFilterByEmailArray(): void
    {
        $this->createEntity('/api/customers', array_merge($this->getCustomerPayload(), ['email' => 'john.doe@example.com']));
        $this->createEntity('/api/customers', array_merge($this->getCustomerPayload(), ['email' => 'jane.doe@example.com']));
        $this->createEntity('/api/customers', array_merge($this->getCustomerPayload(), ['email' => 'jake.doe@example.com']));

        $client = self::createClient();
        $response = $client->request('GET', '/api/customers', [
            'query' => [
                'email[]' => ['john.doe@example.com', 'jane.doe@example.com'],
            ],
        ]);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertSame('john.doe@example.com', $data['member'][0]['email']);
        $this->assertSame('jane.doe@example.com', $data['member'][1]['email']);
    }

    public function testFilterByPhone(): void
    {
        $this->createEntity('/api/customers', array_merge($this->getCustomerPayload(), ['phone' => '0123456789']));
        $this->createEntity('/api/customers', array_merge($this->getCustomerPayload(), ['phone' => '3806312833']));

        $client = self::createClient();
        $response = $client->request('GET', '/api/customers', [
            'query' => ['phone' => '0123456789'],
        ]);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertSame('0123456789', $data['member'][0]['phone']);
    }

    public function testFilterByPhoneArray(): void
    {
        $this->createEntity('/api/customers', array_merge($this->getCustomerPayload(), ['phone' => '0123456789']));
        $this->createEntity('/api/customers', array_merge($this->getCustomerPayload(), ['phone' => '0987654321']));
        $this->createEntity('/api/customers', array_merge($this->getCustomerPayload(), ['phone' => '3806312833']));

        $client = self::createClient();
        $response = $client->request('GET', '/api/customers', [
            'query' => [
                'phone[]' => ['0123456789', '0987654321'],
            ],
        ]);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertSame('0123456789', $data['member'][0]['phone']);
        $this->assertSame('0987654321', $data['member'][1]['phone']);
    }

    public function testFilterByLeadSource(): void
    {
        $this->createEntity('/api/customers', array_merge($this->getCustomerPayload(), ['leadSource' => 'Google']));
        $this->createEntity('/api/customers', array_merge($this->getCustomerPayload(), ['leadSource' => 'Reddit']));

        $client = self::createClient();
        $response = $client->request('GET', '/api/customers', [
            'query' => ['leadSource' => 'Google'],
        ]);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertSame('Google', $data['member'][0]['leadSource']);
    }

    public function testFilterByLeadSourceArray(): void
    {
        $this->createEntity('/api/customers', array_merge($this->getCustomerPayload(), ['leadSource' => 'Google']));
        $this->createEntity('/api/customers', array_merge($this->getCustomerPayload(), ['leadSource' => 'Bing']));
        $this->createEntity('/api/customers', array_merge($this->getCustomerPayload(), ['leadSource' => 'Reddit']));

        $client = self::createClient();
        $response = $client->request('GET', '/api/customers', [
            'query' => [
                'leadSource[]' => ['Google', 'Bing'],
            ],
        ]);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertSame('Google', $data['member'][0]['leadSource']);
        $this->assertSame('Bing', $data['member'][1]['leadSource']);
    }

    public function testFilterByConfirmed(): void
    {
        $this->createEntity('/api/customers', $this->getCustomerPayload());
        $falsePayload = $this->getCustomerPayload();
        $falsePayload['confirmed'] = false;
        $this->createEntity('/api/customers', $falsePayload);

        $client = self::createClient();
        $response = $client->request('GET', '/api/customers', [
            'query' => ['confirmed' => 'true'],
        ]);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertTrue($data['member'][0]['confirmed']);
    }

    public function testFilterByConfirmedArray(): void
    {
        $this->createEntity('/api/customers', $this->getCustomerPayload());
        $falsePayload = $this->getCustomerPayload();
        $falsePayload['confirmed'] = false;
        $this->createEntity('/api/customers', $falsePayload);

        $client = self::createClient();
        $response = $client->request('GET', '/api/customers', [
            'query' => [
                'confirmed[]' => ['true', 'false'],
            ],
        ]);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

    }

    public function testSortedByEmailAsc(): void
    {
        $this->createEntity('/api/customers', array_merge($this->getCustomerPayload(), ['email' => 'alice@example.com']));
        $this->createEntity('/api/customers', array_merge($this->getCustomerPayload(), ['email' => 'bob@example.com']));

        $client = self::createClient();
        $response = $client->request('GET', '/api/customers', [
            'query' => ['order[email]' => 'asc'],
        ]);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertSame('alice@example.com', $data['member'][0]['email']);
        $this->assertSame('bob@example.com', $data['member'][1]['email']);
    }

    public function testSortedByEmailDesc(): void
    {
        $this->createEntity('/api/customers', array_merge($this->getCustomerPayload(), ['email' => 'alice@example.com']));
        $this->createEntity('/api/customers', array_merge($this->getCustomerPayload(), ['email' => 'bob@example.com']));

        $client = self::createClient();
        $response = $client->request('GET', '/api/customers', [
            'query' => ['order[email]' => 'desc'],
        ]);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertSame('bob@example.com', $data['member'][0]['email']);
        $this->assertSame('alice@example.com', $data['member'][1]['email']);
    }

    public function testCursorPagination(): void
    {
        $iri1 = $this->createEntity('/api/customers', $this->getCustomerPayload('One'));
        $iri2 = $this->createEntity('/api/customers', $this->getCustomerPayload('Two'));
        $iri3 = $this->createEntity('/api/customers', $this->getCustomerPayload('Three'));

        $lastUlid = $this->extractUlid($iri3);
        $client = self::createClient();
        $response = $client->request('GET', '/api/customers', [
            'query' => [
                'order[ulid]' => 'desc',
                'ulid[lt]' => $lastUlid,
            ],
        ]);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertCount(2, $data['member']);
    }

    public function testCursorPaginationWithItemsPerPage(): void
    {
        $iri1 = $this->createEntity('/api/customers', $this->getCustomerPayload('One'));
        $iri2 = $this->createEntity('/api/customers', $this->getCustomerPayload('Two'));
        $iri3 = $this->createEntity('/api/customers', $this->getCustomerPayload('Three'));

        $lastUlid = $this->extractUlid($iri3);
        $client = self::createClient();
        $response = $client->request('GET', '/api/customers', [
            'query' => [
                'itemsPerPage' => '1',
                'order[ulid]' => 'desc',
                'ulid[lt]' => $lastUlid,
            ],
        ]);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertCount(1, $data['member']);
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

    public function testPostCustomerSuccess(): void
    {
        $payload = $this->getCustomerPayload('Jane Doe');

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
