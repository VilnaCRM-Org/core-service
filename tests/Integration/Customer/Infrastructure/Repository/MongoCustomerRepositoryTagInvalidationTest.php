<?php

declare(strict_types=1);

namespace Tests\Integration\Customer\Infrastructure\Repository;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Core\Customer\Infrastructure\Repository\MongoStatusRepository;
use App\Core\Customer\Infrastructure\Repository\MongoTypeRepository;
use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Infrastructure\Cache\CacheKeyBuilder;
use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\Binary;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Ulid as SymfonyUlid;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class MongoCustomerRepositoryTagInvalidationTest extends KernelTestCase
{
    private CustomerRepositoryInterface $repository;
    private MongoTypeRepository $typeRepository;
    private MongoStatusRepository $statusRepository;
    private TagAwareCacheInterface $cache;
    private CacheItemPoolInterface $cachePool;
    private CacheKeyBuilder $cacheKeyBuilder;
    private DocumentManager $documentManager;
    private ?CustomerType $defaultType = null;
    private ?CustomerStatus $defaultStatus = null;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->repository = self::getContainer()->get(CustomerRepositoryInterface::class);
        $this->typeRepository = self::getContainer()->get(MongoTypeRepository::class);
        $this->statusRepository = self::getContainer()->get(MongoStatusRepository::class);
        $this->cache = self::getContainer()->get('cache.customer');
        $this->cachePool = self::getContainer()->get('cache.customer');
        $this->cacheKeyBuilder = self::getContainer()->get(CacheKeyBuilder::class);
        $this->documentManager = self::getContainer()->get('doctrine_mongodb.odm.document_manager');

        $this->cachePool->clear();
        $this->ensureDefaultTypeAndStatus();
    }

    public function testInvalidateBySpecificCustomerTag(): void
    {
        $customer1 = $this->createTestCustomer(
            'Customer 1',
            sprintf('customer1+%s@example.com', (string) $this->generateUlid())
        );
        $customer2 = $this->createTestCustomer(
            'Customer 2',
            sprintf('customer2+%s@example.com', (string) $this->generateUlid())
        );

        $result1a = $this->repository->find($customer1->getUlid());
        $result2a = $this->repository->find($customer2->getUlid());

        self::assertSame('Customer 1', $result1a->getInitials());
        self::assertSame('Customer 2', $result2a->getInitials());

        $this->updateCustomerDirectly($customer1->getUlid(), 'Updated Customer 1');
        $this->documentManager->clear();

        $this->cache->invalidateTags(["customer.{$customer1->getUlid()}"]);

        $result1b = $this->repository->find($customer1->getUlid());
        self::assertSame('Updated Customer 1', $result1b->getInitials());

        $result2b = $this->repository->find($customer2->getUlid());
        self::assertSame('Customer 2', $result2b->getInitials());
    }

    public function testInvalidateAllCustomersByTag(): void
    {
        $customers = $this->createCustomersForTagInvalidation();

        $this->warmCustomerDetailCaches($customers);
        $this->updateCustomersDirectly($customers);
        $this->documentManager->clear();

        $this->cache->invalidateTags(['customer']);

        $this->assertUpdatedCustomerInitials($customers);
    }

    /**
     * CRITICAL TEST: Verify cache actually returns stale data when not invalidated.
     * This proves the cache is being used (not just populated).
     */
    public function testCacheReturnsStaleDataWhenNotInvalidated(): void
    {
        $customer = $this->createTestCustomer(
            'Original Name',
            sprintf('stale-test+%s@example.com', (string) $this->generateUlid())
        );

        $this->assertDetailCacheReturnsStaleData($customer);
        $this->cache->invalidateTags(["customer.{$customer->getUlid()}"]);
        $this->assertCustomerDetailInitials($customer, 'Updated Name');
    }

    /**
     * Test email lookup cache returns stale data when not invalidated.
     */
    public function testEmailCacheReturnsStaleDataWhenNotInvalidated(): void
    {
        $email = sprintf('email-stale+%s@example.com', (string) $this->generateUlid());
        $customer = $this->createTestCustomer('Original Email Name', $email);

        $this->assertEmailCacheReturnsStaleData($customer, $email);

        $emailHash = $this->cacheKeyBuilder->hashEmail($email);
        $this->cache->invalidateTags(["customer.email.{$emailHash}"]);
        $this->assertCustomerEmailInitials($email, 'Updated Email Name');
    }

    public function testManagedTypeChangeInvalidatesRelatedCustomerTags(): void
    {
        $type = new CustomerType(
            sprintf('business-%s', (string) $this->generateUlid()),
            $this->generateUlid()
        );
        $this->typeRepository->save($type);

        $customer = $this->createTestCustomerWithReferences(
            'Type Reference Customer',
            sprintf('type-reference+%s@example.com', (string) $this->generateUlid()),
            $type,
            $this->defaultStatus()
        );
        $detailKey = $this->cacheKeyBuilder->buildCustomerKey((string) $customer->getUlid());
        $lookupKey = $this->cacheKeyBuilder->buildCustomerEmailKey($customer->getEmail());

        $this->warmCustomerLookupCaches($customer);
        $this->warmReferenceCaches('type-change');
        $this->assertRelatedCustomerCachesHit($detailKey, $lookupKey, 'type-change');

        $type->setValue(sprintf('enterprise-%s', (string) $this->generateUlid()));
        $this->typeRepository->save($type);

        $this->assertRelatedCustomerCachesMiss($detailKey, $lookupKey, 'type-change');
    }

    public function testManagedStatusChangeInvalidatesRelatedCustomerTags(): void
    {
        $status = new CustomerStatus(
            sprintf('pending-%s', (string) $this->generateUlid()),
            $this->generateUlid()
        );
        $this->statusRepository->save($status);

        $customer = $this->createTestCustomerWithReferences(
            'Status Reference Customer',
            sprintf('status-reference+%s@example.com', (string) $this->generateUlid()),
            $this->defaultType(),
            $status
        );
        $detailKey = $this->cacheKeyBuilder->buildCustomerKey((string) $customer->getUlid());
        $lookupKey = $this->cacheKeyBuilder->buildCustomerEmailKey($customer->getEmail());

        $this->warmCustomerLookupCaches($customer);
        $this->warmReferenceCaches('status-change');
        $this->assertRelatedCustomerCachesHit($detailKey, $lookupKey, 'status-change');

        $status->setValue(sprintf('qualified-%s', (string) $this->generateUlid()));
        $this->statusRepository->save($status);

        $this->assertRelatedCustomerCachesMiss($detailKey, $lookupKey, 'status-change');
    }

    public function testCustomTypeRepositoryDeleteByValueInvalidatesRelatedCustomerTags(): void
    {
        $typeValue = sprintf('custom-delete-type-%s', (string) $this->generateUlid());
        $type = new CustomerType($typeValue, $this->generateUlid());
        $this->typeRepository->save($type);

        $customer = $this->createTestCustomerWithReferences(
            'Custom Type Delete Customer',
            sprintf('custom-type-delete+%s@example.com', (string) $this->generateUlid()),
            $type,
            $this->defaultStatus()
        );
        $detailKey = $this->cacheKeyBuilder->buildCustomerKey((string) $customer->getUlid());
        $lookupKey = $this->cacheKeyBuilder->buildCustomerEmailKey($customer->getEmail());

        $this->warmCustomerLookupCaches($customer);
        $this->warmReferenceCaches('custom-type-delete');
        $this->assertRelatedCustomerCachesHit($detailKey, $lookupKey, 'custom-type-delete');

        $this->typeRepository->deleteByValue($typeValue);

        $this->assertRelatedCustomerCachesMiss($detailKey, $lookupKey, 'custom-type-delete');
    }

    public function testCustomStatusRepositoryDeleteByValueInvalidatesRelatedCustomerTags(): void
    {
        $statusValue = sprintf('custom-delete-status-%s', (string) $this->generateUlid());
        $status = new CustomerStatus($statusValue, $this->generateUlid());
        $this->statusRepository->save($status);

        $customer = $this->createTestCustomerWithReferences(
            'Custom Status Delete Customer',
            sprintf('custom-status-delete+%s@example.com', (string) $this->generateUlid()),
            $this->defaultType(),
            $status
        );
        $detailKey = $this->cacheKeyBuilder->buildCustomerKey((string) $customer->getUlid());
        $lookupKey = $this->cacheKeyBuilder->buildCustomerEmailKey($customer->getEmail());

        $this->warmCustomerLookupCaches($customer);
        $this->warmReferenceCaches('custom-status-delete');
        $this->assertRelatedCustomerCachesHit($detailKey, $lookupKey, 'custom-status-delete');

        $this->statusRepository->deleteByValue($statusValue);

        $this->assertRelatedCustomerCachesMiss($detailKey, $lookupKey, 'custom-status-delete');
    }

    /**
     * Test that cache item is marked as hit on subsequent reads.
     *
     * Note: This verifies cache hit status but does not measure actual database queries.
     */
    public function testCacheHitDoesNotQueryDatabase(): void
    {
        $customer = $this->createTestCustomer(
            'Query Count Test',
            sprintf('query-count+%s@example.com', (string) $this->generateUlid())
        );

        // First read - cache miss, queries database
        $this->repository->find($customer->getUlid());

        // Clear document manager to ensure no identity map interference
        $this->documentManager->clear();

        // Second read - should be cache hit
        $result = $this->repository->find($customer->getUlid());

        self::assertNotNull($result);
        self::assertSame('Query Count Test', $result->getInitials());

        // Verify cache item exists and is hit
        $cacheItem = $this->cachePool->getItem('customer.' . $customer->getUlid());
        self::assertTrue(
            $cacheItem->isHit(),
            'Cache should contain the customer after first read'
        );
    }

    private function ensureDefaultTypeAndStatus(): void
    {
        $type = $this->typeRepository->findOneByCriteria(['value' => 'individual'])
            ?? new CustomerType('individual', $this->generateUlid());
        self::assertInstanceOf(CustomerType::class, $type);
        $this->defaultType = $type;
        $this->typeRepository->save($this->defaultType);

        $status = $this->statusRepository->findOneByCriteria(['value' => 'active'])
            ?? new CustomerStatus('active', $this->generateUlid());
        self::assertInstanceOf(CustomerStatus::class, $status);
        $this->defaultStatus = $status;
        $this->statusRepository->save($this->defaultStatus);
    }

    private function createTestCustomer(string $initials, string $email): Customer
    {
        return $this->createTestCustomerWithReferences(
            $initials,
            $email,
            $this->defaultType(),
            $this->defaultStatus()
        );
    }

    private function createTestCustomerWithReferences(
        string $initials,
        string $email,
        CustomerType $type,
        CustomerStatus $status
    ): Customer {
        $customer = new Customer(
            initials: $initials,
            email: $email,
            phone: '+1234567890',
            leadSource: 'test',
            type: $type,
            status: $status,
            confirmed: true,
            ulid: $this->generateUlid()
        );

        $this->repository->save($customer);

        return $customer;
    }

    private function defaultType(): CustomerType
    {
        $defaultType = $this->defaultType;
        self::assertInstanceOf(CustomerType::class, $defaultType);

        return $defaultType;
    }

    private function defaultStatus(): CustomerStatus
    {
        $defaultStatus = $this->defaultStatus;
        self::assertInstanceOf(CustomerStatus::class, $defaultStatus);

        return $defaultStatus;
    }

    /**
     * @return list<Customer>
     */
    private function createCustomersForTagInvalidation(): array
    {
        return [
            $this->createNumberedCustomer(1),
            $this->createNumberedCustomer(2),
            $this->createNumberedCustomer(3),
        ];
    }

    private function createNumberedCustomer(int $number): Customer
    {
        return $this->createTestCustomer(
            sprintf('Customer %d', $number),
            sprintf('customer%d+%s@example.com', $number, (string) $this->generateUlid())
        );
    }

    /**
     * @param list<Customer> $customers
     */
    private function warmCustomerDetailCaches(array $customers): void
    {
        $this->repository->find($customers[0]->getUlid());
        $this->repository->find($customers[1]->getUlid());
        $this->repository->find($customers[2]->getUlid());
    }

    /**
     * @param list<Customer> $customers
     */
    private function updateCustomersDirectly(array $customers): void
    {
        $this->updateCustomerDirectly($customers[0]->getUlid(), 'Updated 1');
        $this->updateCustomerDirectly($customers[1]->getUlid(), 'Updated 2');
        $this->updateCustomerDirectly($customers[2]->getUlid(), 'Updated 3');
    }

    /**
     * @param list<Customer> $customers
     */
    private function assertUpdatedCustomerInitials(array $customers): void
    {
        $this->assertCustomerDetailInitials($customers[0], 'Updated 1');
        $this->assertCustomerDetailInitials($customers[1], 'Updated 2');
        $this->assertCustomerDetailInitials($customers[2], 'Updated 3');
    }

    private function assertDetailCacheReturnsStaleData(Customer $customer): void
    {
        $this->assertCustomerDetailInitials($customer, 'Original Name');

        $this->updateCustomerDirectly($customer->getUlid(), 'Updated Name');
        $this->documentManager->clear();

        $this->assertCustomerDetailInitials($customer, 'Original Name');
    }

    private function assertEmailCacheReturnsStaleData(Customer $customer, string $email): void
    {
        $this->assertCustomerEmailInitials($email, 'Original Email Name');

        $this->updateCustomerDirectly($customer->getUlid(), 'Updated Email Name');
        $this->documentManager->clear();

        $this->assertCustomerEmailInitials($email, 'Original Email Name');
    }

    private function assertCustomerDetailInitials(
        Customer $customer,
        string $expectedInitials
    ): void {
        $result = $this->repository->find($customer->getUlid());
        self::assertSame($expectedInitials, $result->getInitials());
    }

    private function assertCustomerEmailInitials(
        string $email,
        string $expectedInitials
    ): void {
        $result = $this->repository->findByEmail($email);
        self::assertSame($expectedInitials, $result->getInitials());
    }

    private function updateCustomerDirectly(string $customerId, string $newInitials): void
    {
        $result = $this->documentManager
            ->getDocumentCollection(Customer::class)
            ->updateOne(
                ['_id' => new Binary((new Ulid($customerId))->toBinary(), Binary::TYPE_GENERIC)],
                ['$set' => ['initials' => $newInitials]]
            );

        self::assertSame(
            1,
            $result->getMatchedCount(),
            "Customer {$customerId} not found for raw update"
        );
    }

    private function warmCustomerLookupCaches(Customer $customer): void
    {
        self::assertNotNull($this->repository->find($customer->getUlid()));
        self::assertNotNull($this->repository->findByEmail($customer->getEmail()));
    }

    private function warmReferenceCaches(string $scenario): void
    {
        $this->warmTaggedCacheItem(
            $this->collectionCacheKey($scenario),
            'customer.collection'
        );
        $this->warmTaggedCacheItem(
            $this->referenceCacheKey($scenario),
            'customer.reference'
        );
    }

    private function assertRelatedCustomerCachesHit(
        string $detailKey,
        string $lookupKey,
        string $scenario
    ): void {
        $this->assertCacheHit($detailKey);
        $this->assertCacheHit($lookupKey);
        $this->assertCacheHit($this->collectionCacheKey($scenario));
        $this->assertCacheHit($this->referenceCacheKey($scenario));
    }

    private function assertRelatedCustomerCachesMiss(
        string $detailKey,
        string $lookupKey,
        string $scenario
    ): void {
        $this->assertCacheMiss($detailKey);
        $this->assertCacheMiss($lookupKey);
        $this->assertCacheMiss($this->collectionCacheKey($scenario));
        $this->assertCacheMiss($this->referenceCacheKey($scenario));
    }

    private function assertCacheHit(string $cacheKey): void
    {
        self::assertTrue($this->cachePool->getItem($cacheKey)->isHit());
    }

    private function assertCacheMiss(string $cacheKey): void
    {
        self::assertFalse($this->cachePool->getItem($cacheKey)->isHit());
    }

    private function collectionCacheKey(string $scenario): string
    {
        return "customer.collection.{$scenario}";
    }

    private function referenceCacheKey(string $scenario): string
    {
        return "customer.reference.{$scenario}";
    }

    private function generateUlid(): Ulid
    {
        return new Ulid((string) new SymfonyUlid());
    }

    private function warmTaggedCacheItem(string $key, string $tag): void
    {
        $this->cache->get(
            $key,
            static function (ItemInterface $item) use ($tag): string {
                $item->tag($tag);

                return 'warmed';
            }
        );
    }
}
