<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Customer\Domain\Entity\CustomerStatus;
use App\Customer\Domain\Entity\CustomerType;
use DateInterval;
use DateTime;
use DateTimeZone;

class CustomerDateFilterApITest extends BaseIntegrationTest
{
    public function testFilterByUpdatedAtAfter(): void
    {
        $this->createEntity('/api/customers', $this->getCustomer());

        $date = new DateTime('now', new DateTimeZone('UTC'));
        $future = $date->add(new DateInterval('P1Y'))->format('Y-m-d\\TH:i:s\\Z');

        $client = self::createClient();
        $response = $client->request('GET', '/api/customers', [
            'query' => ['updatedAt[after]' => $future],
        ]);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertSame(0, $data['totalItems']);
        $this->assertCount(0, $data['member']);
    }

    public function testFilterByUpdatedAtStrictlyAfter(): void
    {
        $this->createEntity('/api/customers', $this->getCustomer());

        $date = new DateTime('now', new DateTimeZone('UTC'));
        $future = $date->add(new DateInterval('P1Y'))->format('Y-m-d\\TH:i:s\\Z');

        $client = self::createClient();
        $response = $client->request('GET', '/api/customers', [
            'query' => ['updatedAt[strictly_after]' => $future],
        ]);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertSame(0, $data['totalItems']);
        $this->assertCount(0, $data['member']);
    }

    public function testFilterByCreatedAtBefore(): void
    {
        $iri = $this->createEntity('/api/customers', $this->getCustomer());
        $ulid = $this->extractUlid($iri);

        $date = new DateTime('now', new DateTimeZone('UTC'));
        $future = $date->add(new DateInterval('P1Y'))->format('Y-m-d\\TH:i:s\\Z');

        $client = self::createClient();
        $response = $client->request('GET', '/api/customers', [
            'query' => ['createdAt[before]' => $future],
        ]);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertSame(1, $data['totalItems']);
        $this->assertCount(1, $data['member']);
        $this->assertStringContainsString($ulid, $data['member'][0]['@id']);
        $this->assertSame('PartialCollectionView', $data['view']['@type']);
    }

    public function testFilterByCreatedAtStrictlyBefore(): void
    {
        $iri = $this->createEntity('/api/customers', $this->getCustomer());
        $ulid = $this->extractUlid($iri);

        $date = new DateTime('now', new DateTimeZone('UTC'));
        $future = $date->add(new DateInterval('P1Y'))->format('Y-m-d\\TH:i:s\\Z');

        $client = self::createClient();
        $response = $client->request('GET', '/api/customers', [
            'query' => ['createdAt[strictly_before]' => $future],
        ]);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertSame(1, $data['totalItems']);
        $this->assertCount(1, $data['member']);
        $this->assertStringContainsString($ulid, $data['member'][0]['@id']);
        $this->assertSame('PartialCollectionView', $data['view']['@type']);
    }

    public function testFilterByCreatedAtAfter(): void
    {
        $iri = $this->createEntity('/api/customers', $this->getCustomer());
        $ulid = $this->extractUlid($iri);

        $date = new DateTime('now', new DateTimeZone('UTC'));
        $past = $date->sub(new DateInterval('P1Y'))->format('Y-m-d\\TH:i:s\\Z');

        $client = self::createClient();
        $response = $client->request('GET', '/api/customers', [
            'query' => ['createdAt[after]' => $past],
        ]);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertSame(1, $data['totalItems']);
        $this->assertCount(1, $data['member']);
        $this->assertStringContainsString($ulid, $data['member'][0]['@id']);
        $this->assertSame('PartialCollectionView', $data['view']['@type']);
    }

    public function testFilterByCreatedAtStrictlyAfter(): void
    {
        $iri = $this->createEntity('/api/customers', $this->getCustomer());
        $ulid = $this->extractUlid($iri);

        $date = new DateTime('now', new DateTimeZone('UTC'));
        $past = $date->sub(new DateInterval('P1Y'))->format('Y-m-d\\TH:i:s\\Z');

        $client = self::createClient();
        $response = $client->request('GET', '/api/customers', [
            'query' => ['createdAt[strictly_after]' => $past],
        ]);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertCount(1, $data['member']);
        $this->assertStringContainsString($ulid, $data['member'][0]['@id']);
        $this->assertSame('PartialCollectionView', $data['view']['@type']);
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
