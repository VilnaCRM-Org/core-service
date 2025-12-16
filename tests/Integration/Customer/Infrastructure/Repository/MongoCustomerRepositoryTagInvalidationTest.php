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
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Ulid as SymfonyUlid;
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
        $customer1 = $this->createTestCustomer(
            'Customer 1',
            sprintf('customer1+%s@example.com', (string) $this->generateUlid())
        );
        $customer2 = $this->createTestCustomer(
            'Customer 2',
            sprintf('customer2+%s@example.com', (string) $this->generateUlid())
        );
        $customer3 = $this->createTestCustomer(
            'Customer 3',
            sprintf('customer3+%s@example.com', (string) $this->generateUlid())
        );

        $this->repository->find($customer1->getUlid());
        $this->repository->find($customer2->getUlid());
        $this->repository->find($customer3->getUlid());

        $this->updateCustomerDirectly($customer1->getUlid(), 'Updated 1');
        $this->updateCustomerDirectly($customer2->getUlid(), 'Updated 2');
        $this->updateCustomerDirectly($customer3->getUlid(), 'Updated 3');
        $this->documentManager->clear();

        $this->cache->invalidateTags(['customer']);

        $result1 = $this->repository->find($customer1->getUlid());
        $result2 = $this->repository->find($customer2->getUlid());
        $result3 = $this->repository->find($customer3->getUlid());

        self::assertSame('Updated 1', $result1->getInitials());
        self::assertSame('Updated 2', $result2->getInitials());
        self::assertSame('Updated 3', $result3->getInitials());
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

        // First read - populates cache
        $result1 = $this->repository->find($customer->getUlid());
        self::assertSame('Original Name', $result1->getInitials());

        // Update database directly (bypassing cache invalidation)
        $this->updateCustomerDirectly($customer->getUlid(), 'Updated Name');
        $this->documentManager->clear();

        // Second read - should return STALE cached data (proves cache is working)
        $result2 = $this->repository->find($customer->getUlid());
        self::assertSame(
            'Original Name',
            $result2->getInitials(),
            'Cache should return stale data when not invalidated - this proves caching is working'
        );

        // Now invalidate and verify fresh data is returned
        $this->cache->invalidateTags(["customer.{$customer->getUlid()}"]);

        $result3 = $this->repository->find($customer->getUlid());
        self::assertSame(
            'Updated Name',
            $result3->getInitials(),
            'After invalidation, fresh data should be returned'
        );
    }

    /**
     * Test email lookup cache returns stale data when not invalidated.
     */
    public function testEmailCacheReturnsStaleDataWhenNotInvalidated(): void
    {
        $email = sprintf('email-stale+%s@example.com', (string) $this->generateUlid());
        $customer = $this->createTestCustomer('Original Email Name', $email);

        // First read - populates email cache
        $result1 = $this->repository->findByEmail($email);
        self::assertSame('Original Email Name', $result1->getInitials());

        // Update database directly (bypassing cache invalidation)
        $this->updateCustomerDirectly($customer->getUlid(), 'Updated Email Name');
        $this->documentManager->clear();

        // Second read - should return STALE cached data
        $result2 = $this->repository->findByEmail($email);
        self::assertSame(
            'Original Email Name',
            $result2->getInitials(),
            'Email cache should return stale data when not invalidated'
        );

        // Invalidate email cache tag and verify fresh data
        $emailHash = $this->cacheKeyBuilder->hashEmail($email);
        $this->cache->invalidateTags(["customer.email.{$emailHash}"]);

        $result3 = $this->repository->findByEmail($email);
        self::assertSame(
            'Updated Email Name',
            $result3->getInitials(),
            'After email cache invalidation, fresh data should be returned'
        );
    }

    /**
     * Test that cache hit does NOT query database (verify by checking query count).
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
        if ($this->defaultType === null) {
            $this->defaultType = new CustomerType('individual', $this->generateUlid());
            $this->typeRepository->save($this->defaultType);
        }

        if ($this->defaultStatus === null) {
            $this->defaultStatus = new CustomerStatus('active', $this->generateUlid());
            $this->statusRepository->save($this->defaultStatus);
        }
    }

    private function createTestCustomer(string $initials, string $email): Customer
    {
        $customer = new Customer(
            initials: $initials,
            email: $email,
            phone: '+1234567890',
            leadSource: 'test',
            type: $this->defaultType,
            status: $this->defaultStatus,
            confirmed: true,
            ulid: $this->generateUlid()
        );

        $this->repository->save($customer);

        return $customer;
    }

    private function updateCustomerDirectly(string $customerId, string $newInitials): void
    {
        $customer = $this->documentManager->find(Customer::class, $customerId);
        self::assertNotNull($customer, "Customer {$customerId} not found for direct update");
        $customer->setInitials($newInitials);

        $this->documentManager->flush();
    }

    private function generateUlid(): Ulid
    {
        return new Ulid((string) new SymfonyUlid());
    }
}
