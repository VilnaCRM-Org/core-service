<?php

declare(strict_types=1);

namespace Tests\Integration;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Shared\Domain\ValueObject\Ulid;
use Symfony\Component\Uid\Ulid as SymfonyUlid;

/**
 * HTTP Cache Tests for Customer API
 *
 * Tests Cache-Control headers, ETag, and Last-Modified functionality
 */
final class CustomerHttpCacheTest extends ApiTestCase
{
    private string $baseUrl = '/api/customers';

    public function testGetCustomerReturnsCacheControlHeaders(): void
    {
        $client = self::createClient();
        $customer = $this->createTestCustomer();

        $client->request('GET', "{$this->baseUrl}/{$customer->getUlid()}");

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Cache-Control', 'max-age=600, public, s-maxage=600');
        self::assertResponseHasHeader('ETag');
    }

    public function testGetCustomerCollectionReturnsCacheControlHeaders(): void
    {
        $client = self::createClient();
        $this->createTestCustomer();

        $client->request('GET', $this->baseUrl);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Cache-Control', 'max-age=300, public, s-maxage=600');
    }

    public function testETagChangesAfterModification(): void
    {
        $client = self::createClient();
        $customer = $this->createTestCustomer();

        // First request to get initial ETag
        $response1 = $client->request('GET', "{$this->baseUrl}/{$customer->getUlid()}");
        self::assertResponseIsSuccessful();
        $etag1 = $response1->getHeaders()['etag'][0] ?? null;
        self::assertNotNull($etag1, 'ETag header should be present');

        // Modify customer
        $client->request('PATCH', "{$this->baseUrl}/{$customer->getUlid()}", [
            'json' => ['initials' => 'Updated Name'],
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
        ]);
        self::assertResponseIsSuccessful();

        // Request again to get new ETag
        $response2 = $client->request('GET', "{$this->baseUrl}/{$customer->getUlid()}");
        self::assertResponseIsSuccessful();
        $etag2 = $response2->getHeaders()['etag'][0] ?? null;
        self::assertNotNull($etag2, 'ETag header should be present after modification');

        // ETag should change after modification
        self::assertNotEquals($etag1, $etag2, 'ETag should change after modification');
    }

    private function createTestCustomer(): Customer
    {
        $container = self::getContainer();
        $em = $container->get('doctrine_mongodb.odm.document_manager');

        // Create and persist type and status
        $type = new CustomerType('individual', $this->generateUlid());
        $status = new CustomerStatus('active', $this->generateUlid());

        $em->persist($type);
        $em->persist($status);

        // Create customer
        $customer = new Customer(
            initials: 'Test Customer',
            email: sprintf('test+%s@example.com', (string) $this->generateUlid()),
            phone: '+1234567890',
            leadSource: 'test',
            type: $type,
            status: $status,
            confirmed: true,
            ulid: $this->generateUlid()
        );

        $em->persist($customer);
        $em->flush();

        return $customer;
    }

    private function generateUlid(): Ulid
    {
        return new Ulid((string) new SymfonyUlid());
    }
}
