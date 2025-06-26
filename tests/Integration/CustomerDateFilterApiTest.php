<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;

final class CustomerDateFilterApiTest extends BaseIntegrationTest
{
    public function testFilterByUpdatedAtAfter(): void
    {
        $this->createEntity('/api/customers', $this->getCustomer());

        $future = $this->getFutureDate();
        $this->assertDateFilterResults('updatedAt[after]', $future, 0);
    }

    public function testFilterByUpdatedAtStrictlyAfter(): void
    {
        $this->createEntity('/api/customers', $this->getCustomer());

        $future = $this->getFutureDate();
        $this->assertDateFilterResults('updatedAt[strictly_after]', $future, 0);
    }

    public function testFilterByCreatedAtBefore(): void
    {
        $iri = $this->createEntity('/api/customers', $this->getCustomer());
        $ulid = $this->extractUlid($iri);

        $future = $this->getFutureDate();
        $this->assertDateFilterResults('createdAt[before]', $future, 1, $ulid);
    }

    public function testFilterByCreatedAtStrictlyBefore(): void
    {
        $iri = $this->createEntity('/api/customers', $this->getCustomer());
        $ulid = $this->extractUlid($iri);

        $future = $this->getFutureDate();
        $this->assertDateFilterResults(
            'createdAt[strictly_before]',
            $future,
            1,
            $ulid
        );
    }

    public function testFilterByCreatedAtAfter(): void
    {
        $iri = $this->createEntity('/api/customers', $this->getCustomer());
        $ulid = $this->extractUlid($iri);

        $past = $this->getPastDate();
        $this->assertDateFilterResults('createdAt[after]', $past, 1, $ulid);
    }

    public function testFilterByCreatedAtStrictlyAfter(): void
    {
        $iri = $this->createEntity('/api/customers', $this->getCustomer());
        $ulid = $this->extractUlid($iri);

        $past = $this->getPastDate();
        $this->assertDateFilterResults(
            'createdAt[strictly_after]',
            $past,
            1,
            $ulid
        );
    }

    private function assertDateFilterResults(
        string $filterParam,
        string $date,
        int $expectedCount,
        ?string $expectedUlid = null
    ): void {
        $client = self::createClient();
        $response = $client->request('GET', '/api/customers', [
            'query' => [$filterParam => $date],
        ]);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertSame($expectedCount, $data['totalItems']);
        $this->assertCount($expectedCount, $data['member']);

        if ($expectedCount > 0 && $expectedUlid !== null) {
            $this->assertStringContainsString(
                $expectedUlid,
                $data['member'][0]['@id']
            );
            $this->assertSame('PartialCollectionView', $data['view']['@type']);
        }
    }

    private function getFutureDate(): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone('UTC')))
            ->add(new DateInterval('P1Y'))
            ->format('Y-m-d\\TH:i:s\\Z');
    }

    private function getPastDate(): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone('UTC')))
            ->sub(new DateInterval('P1Y'))
            ->format('Y-m-d\\TH:i:s\\Z');
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

    private function extractUlid(string $iri): string
    {
        return substr($iri, strrpos($iri, '/') + 1);
    }
}
