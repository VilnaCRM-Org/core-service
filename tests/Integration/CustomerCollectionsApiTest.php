<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Customer\Domain\Entity\CustomerStatus;
use App\Customer\Domain\Entity\CustomerType;

final class CustomerCollectionsApiTest extends BaseIntegrationTest
{
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
        $payloadA = $this->getCustomer('JD');
        $this->createEntity('/api/customers', $payloadA);
        $payloadB = $this->getCustomer('FJ');
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
        $payloadA = $this->getCustomer('AB');
        $this->createEntity('/api/customers', $payloadA);
        $payloadB = $this->getCustomer('CD');
        $this->createEntity('/api/customers', $payloadB);
        $payloadC = $this->getCustomer('DC');
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
        $this->createCustomerWithEmails(
            ['john.doe@example.com', 'jane.doe@example.com']
        );

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
        $this->createCustomerWithEmails(
            ['john.doe@example.com', 'jane.doe@example.com']
        );

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
        $this->createEntity(
            '/api/customers',
            array_merge($this->getCustomer(), ['phone' => '0123456789'])
        );
        $this->createEntity(
            '/api/customers',
            array_merge($this->getCustomer(), ['phone' => '3806312833'])
        );

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
        $this->createCustomerWithPhones();

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
        $this->createEntity(
            '/api/customers',
            array_merge($this->getCustomer(), ['leadSource' => 'Google'])
        );
        $this->createEntity(
            '/api/customers',
            array_merge($this->getCustomer(), ['leadSource' => 'Reddit'])
        );

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
        $this->createCustomerWithLeadSource();

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
        $this->createEntity('/api/customers', $this->getCustomer());
        $falsePayload = $this->getCustomer();
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
        $this->createEntity('/api/customers', $this->getCustomer());
        $falsePayload = $this->getCustomer();
        $falsePayload['confirmed'] = false;
        $this->createEntity('/api/customers', $falsePayload);

        $client = self::createClient();
        $client->request('GET', '/api/customers', [
            'query' => [
                'confirmed[]' => ['true', 'false'],
            ],
        ]);
        $this->assertResponseIsSuccessful();
    }

    public function testSortedByEmailAsc(): void
    {
        $this->createEntity(
            '/api/customers',
            array_merge($this->getCustomer(), ['email' => 'alice@example.com'])
        );
        $this->createEntity(
            '/api/customers',
            array_merge($this->getCustomer(), ['email' => 'bob@example.com'])
        );

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
        $this->createEntity(
            '/api/customers',
            array_merge($this->getCustomer(), ['email' => 'alice@example.com'])
        );
        $this->createEntity(
            '/api/customers',
            array_merge($this->getCustomer(), ['email' => 'bob@example.com'])
        );

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
        $ulid3 = $this->createThreeEntities()[2];

        $client = self::createClient();
        $response = $client->request('GET', '/api/customers', [
            'query' => [
                'order[ulid]' => 'desc',
                'ulid[lt]' => $ulid3,
            ],
        ]);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertCount(2, $data['member']);
    }

    public function testFilterUlidLt(): void
    {
        $ulid3 = $this->createThreeEntities()[2];

        $client = self::createClient();
        $response = $client->request('GET', '/api/customers', [
            'query' => ['ulid[lt]' => $ulid3],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertCount(2, $data['member']);
    }

    public function testFilterUlidLte(): void
    {
        $ulid2 = $this->createThreeEntities()[1];

        $client = self::createClient();
        $response = $client->request('GET', '/api/customers', [
            'query' => ['ulid[lte]' => $ulid2],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertCount(2, $data['member']);
    }

    public function testFilterUlidGt(): void
    {
        [$ulid1] = $this->createThreeEntities();

        $client = self::createClient();
        $response = $client->request('GET', '/api/customers', [
            'query' => ['ulid[gt]' => $ulid1],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertCount(2, $data['member']);
    }

    public function testFilterUlidGte(): void
    {
        $ulid2 = $this->createThreeEntities()[1];

        $client = self::createClient();
        $response = $client->request('GET', '/api/customers', [
            'query' => ['ulid[gte]' => $ulid2],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertCount(2, $data['member']);
    }

    public function testFilterUlidBetween(): void
    {
        [$ulid1, $ulid2] = $this->createThreeEntities();

        $client = self::createClient();
        $response = $client->request('GET', '/api/customers', [
            'query' => [
                'ulid[between]' => sprintf('%s..%s', $ulid1, $ulid2),
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertCount(2, $data['member']);
    }

    public function testCursorPaginationWithItemsPerPage(): void
    {
        $ulid3 = $this->createThreeEntities()[2];

        $client = self::createClient();
        $response = $client->request('GET', '/api/customers', [
            'query' => [
                'itemsPerPage' => '1',
                'order[ulid]' => 'desc',
                'ulid[lt]' => $ulid3,
            ],
        ]);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertCount(1, $data['member']);
    }

    private function createCustomerWithLeadSource(): void
    {
        $this->createEntity(
            '/api/customers',
            array_merge($this->getCustomer(), ['leadSource' => 'Google'])
        );
        $this->createEntity(
            '/api/customers',
            array_merge($this->getCustomer(), ['leadSource' => 'Bing'])
        );
        $this->createEntity(
            '/api/customers',
            array_merge($this->getCustomer(), ['leadSource' => 'Reddit'])
        );
    }

    private function createCustomerWithPhones(): void
    {
        $this->createEntity(
            '/api/customers',
            array_merge($this->getCustomer(), ['phone' => '0123456789'])
        );
        $this->createEntity(
            '/api/customers',
            array_merge($this->getCustomer(), ['phone' => '0987654321'])
        );
        $this->createEntity(
            '/api/customers',
            array_merge($this->getCustomer(), ['phone' => '3806312833'])
        );
    }

    /**
     * @return array<string>
     */
    private function createThreeEntities(): array
    {
        $iris = [];
        $iris[] = $this->createEntity(
            '/api/customers',
            $this->getCustomer('One')
        );
        $iris[] = $this->createEntity(
            '/api/customers',
            $this->getCustomer('Two')
        );
        $iris[] = $this->createEntity(
            '/api/customers',
            $this->getCustomer('Three')
        );

        return array_map([$this, 'extractUlid'], $iris);
    }

    /**
     * @param array<string> $emails
     */
    private function createCustomerWithEmails(array $emails): void
    {
        foreach ($emails as $email) {
            $this->createEntity(
                '/api/customers',
                array_merge(
                    $this->getCustomer(),
                    ['email' => $email]
                )
            );
        }
    }

    /**
     * @return array<string, string|bool|CustomerType|CustomerStatus>
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

    private function extractUlid(string $iri): string
    {
        return substr($iri, strrpos($iri, '/') + 1);
    }
}
