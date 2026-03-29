<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Infrastructure\Repository;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Core\Customer\Infrastructure\Repository\CachedCustomerRepository;
use App\Core\Customer\Infrastructure\Resolver\CustomerCacheTagResolver;
use App\Shared\Infrastructure\Cache\CacheKeyBuilder;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class CachedCustomerRepositoryTest extends UnitTestCase
{
    private CustomerRepositoryInterface&MockObject $innerRepository;
    private TagAwareCacheInterface&MockObject $cache;
    private CacheKeyBuilder&MockObject $cacheKeyBuilder;
    private LoggerInterface&MockObject $logger;
    private CachedCustomerRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->innerRepository = $this->createMock(CustomerRepositoryInterface::class);
        $this->cache = $this->createMock(TagAwareCacheInterface::class);
        $this->cacheKeyBuilder = $this->createMock(CacheKeyBuilder::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->repository = new CachedCustomerRepository(
            $this->innerRepository,
            $this->cache,
            $this->cacheKeyBuilder,
            new CustomerCacheTagResolver($this->cacheKeyBuilder),
            $this->logger
        );
    }

    public function testFindUsesCacheWithCorrectKey(): void
    {
        $customerId = (string) $this->faker->ulid();
        $cacheKey = 'customer.' . $customerId;
        $customer = $this->createMock(Customer::class);

        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('buildCustomerKey')
            ->with($customerId)
            ->willReturn($cacheKey);

        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with($cacheKey, $this->isType('callable'), 1.0)
            ->willReturn($customer);

        $result = $this->repository->find($customerId);

        self::assertSame($customer, $result);
    }

    public function testFindByEmailUsesCacheWithCorrectKey(): void
    {
        $email = 'test@example.com';
        $cacheKey = 'customer.email.hash123';
        $customer = $this->createMock(Customer::class);

        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('buildCustomerEmailKey')
            ->with($email)
            ->willReturn($cacheKey);

        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with($cacheKey, $this->isType('callable'))
            ->willReturn($customer);

        $result = $this->repository->findByEmail($email);

        self::assertSame($customer, $result);
    }

    public function testFindFallsBackToDatabaseOnCacheError(): void
    {
        $customerId = (string) $this->faker->ulid();
        $cacheKey = 'customer.' . $customerId;
        $customer = $this->createMock(Customer::class);

        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('buildCustomerKey')
            ->with($customerId)
            ->willReturn($cacheKey);

        $this->cache
            ->expects($this->once())
            ->method('get')
            ->willThrowException(new \RuntimeException('Cache unavailable'));

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Cache error - falling back to database',
                $this->callback(static function ($context) use ($cacheKey) {
                    return $context['cache_key'] === $cacheKey
                        && $context['error'] === 'Cache unavailable'
                        && $context['operation'] === 'cache.error';
                })
            );

        $this->innerRepository
            ->expects($this->once())
            ->method('find')
            ->with($customerId, 0, null)
            ->willReturn($customer);

        $result = $this->repository->find($customerId);

        self::assertSame($customer, $result);
    }

    public function testFindByEmailFallsBackToDatabaseOnCacheError(): void
    {
        $email = 'test@example.com';
        $cacheKey = 'customer.email.hash123';
        $customer = $this->createMock(Customer::class);

        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('buildCustomerEmailKey')
            ->with($email)
            ->willReturn($cacheKey);

        $this->cache
            ->expects($this->once())
            ->method('get')
            ->willThrowException(new \RuntimeException('Cache unavailable'));

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Cache error - falling back to database',
                $this->callback(static function ($context) use ($cacheKey) {
                    return $context['cache_key'] === $cacheKey
                        && $context['error'] === 'Cache unavailable'
                        && $context['operation'] === 'cache.error';
                })
            );

        $this->innerRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($customer);

        $result = $this->repository->findByEmail($email);

        self::assertSame($customer, $result);
    }

    public function testSaveDelegatesToInnerRepository(): void
    {
        $customer = $this->createMock(Customer::class);

        $this->innerRepository
            ->expects($this->once())
            ->method('save')
            ->with($customer);

        $this->repository->save($customer);
    }

    public function testDeleteDelegatesToInnerRepository(): void
    {
        $customer = $this->createMock(Customer::class);

        $this->innerRepository
            ->expects($this->once())
            ->method('delete')
            ->with($customer);

        $this->repository->delete($customer);
    }

    public function testDeleteByEmailDelegatesToInnerRepository(): void
    {
        $email = 'test@example.com';
        $emailHash = 'email_hash_123';
        $customer = $this->createConfiguredMock(Customer::class, [
            'getUlid' => (string) $this->faker->ulid(),
            'getEmail' => $email,
        ]);

        $this->innerRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($customer);

        $this->innerRepository
            ->expects($this->once())
            ->method('deleteByEmail')
            ->with($email);

        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('hashEmail')
            ->with($email)
            ->willReturn($emailHash);

        $this->cache
            ->expects($this->once())
            ->method('invalidateTags')
            ->with([
                'customer.collection',
                'customer.' . $customer->getUlid(),
                'customer.email.' . $emailHash,
            ]);

        $this->repository->deleteByEmail($email);
    }

    public function testDeleteByEmailInvalidatesFallbackTagsWhenCustomerLookupMisses(): void
    {
        $email = 'test@example.com';
        $emailHash = 'email_hash_123';

        $this->innerRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);

        $this->innerRepository
            ->expects($this->once())
            ->method('deleteByEmail')
            ->with($email);

        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('hashEmail')
            ->with($email)
            ->willReturn($emailHash);

        $this->cache
            ->expects($this->once())
            ->method('invalidateTags')
            ->with([
                'customer.collection',
                'customer.email.' . $emailHash,
            ]);

        $this->repository->deleteByEmail($email);
    }

    public function testDeleteByEmailLogsWarningWhenCacheInvalidationFails(): void
    {
        $email = 'test@example.com';
        $emailHash = 'email_hash_123';
        $customer = $this->createConfiguredMock(Customer::class, [
            'getUlid' => (string) $this->faker->ulid(),
            'getEmail' => $email,
        ]);

        $this->innerRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($customer);

        $this->innerRepository
            ->expects($this->once())
            ->method('deleteByEmail')
            ->with($email);

        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('hashEmail')
            ->with($email)
            ->willReturn($emailHash);

        $this->cache
            ->expects($this->once())
            ->method('invalidateTags')
            ->willThrowException(new \RuntimeException('Cache backend unavailable'));

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'Cache invalidation failed after customer deletion',
                $this->callback(static function (array $context): bool {
                    return $context['operation'] === 'cache.invalidation.error'
                        && $context['error'] === 'Cache backend unavailable';
                })
            );

        $this->repository->deleteByEmail($email);
    }

    public function testDeleteByIdDelegatesToInnerRepository(): void
    {
        $id = (string) $this->faker->ulid();
        $email = 'test@example.com';
        $emailHash = 'email_hash_123';
        $customer = $this->createConfiguredMock(Customer::class, [
            'getUlid' => $id,
            'getEmail' => $email,
        ]);

        $this->innerRepository
            ->expects($this->once())
            ->method('find')
            ->with($id, 0, null)
            ->willReturn($customer);

        $this->innerRepository
            ->expects($this->once())
            ->method('deleteById')
            ->with($id);

        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('hashEmail')
            ->with($email)
            ->willReturn($emailHash);

        $this->cache
            ->expects($this->once())
            ->method('invalidateTags')
            ->with([
                'customer.collection',
                'customer.' . $id,
                'customer.email.' . $emailHash,
            ]);

        $this->repository->deleteById($id);
    }

    public function testDeleteByIdInvalidatesFallbackTagsWhenCustomerLookupMisses(): void
    {
        $id = (string) $this->faker->ulid();

        $this->innerRepository
            ->expects($this->once())
            ->method('find')
            ->with($id, 0, null)
            ->willReturn(null);

        $this->innerRepository
            ->expects($this->once())
            ->method('deleteById')
            ->with($id);

        $this->cache
            ->expects($this->once())
            ->method('invalidateTags')
            ->with([
                'customer.collection',
                'customer.' . $id,
            ]);

        $this->repository->deleteById($id);
    }

    public function testFindCacheMissLoadsFromDatabase(): void
    {
        $customerId = (string) $this->faker->ulid();
        $cacheKey = 'customer.' . $customerId;
        $customer = $this->createMock(Customer::class);

        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('buildCustomerKey')
            ->with($customerId)
            ->willReturn($cacheKey);

        $cacheItem = $this->createMock(ItemInterface::class);
        $cacheItem
            ->expects($this->once())
            ->method('expiresAfter')
            ->with(600);

        $cacheItem
            ->expects($this->once())
            ->method('tag')
            ->with(['customer', 'customer.' . $customerId]);

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Cache miss - loading customer from database',
                $this->callback(static function ($context) use ($cacheKey, $customerId) {
                    return $context['cache_key'] === $cacheKey
                        && $context['customer_id'] === $customerId
                        && $context['operation'] === 'cache.miss';
                })
            );

        $this->innerRepository
            ->expects($this->once())
            ->method('find')
            ->with($customerId, 0, null)
            ->willReturn($customer);

        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with($cacheKey, $this->isType('callable'), 1.0)
            ->willReturnCallback(static function ($key, $callback) use ($cacheItem) {
                return $callback($cacheItem);
            });

        $result = $this->repository->find($customerId);

        self::assertSame($customer, $result);
    }

    public function testFindByEmailCacheMissLoadsFromDatabase(): void
    {
        $email = 'test@example.com';
        $emailHash = 'hash_abc123';
        $cacheKey = 'customer.email.' . $emailHash;
        $customer = $this->createMock(Customer::class);

        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('buildCustomerEmailKey')
            ->with($email)
            ->willReturn($cacheKey);

        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('hashEmail')
            ->with($email)
            ->willReturn($emailHash);

        $cacheItem = $this->createMock(ItemInterface::class);
        $cacheItem
            ->expects($this->once())
            ->method('expiresAfter')
            ->with(300);

        $cacheItem
            ->expects($this->once())
            ->method('tag')
            ->with(['customer', 'customer.email', 'customer.email.' . $emailHash]);

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Cache miss - loading customer by email',
                $this->callback(static function ($context) use ($cacheKey) {
                    return $context['cache_key'] === $cacheKey
                        && $context['operation'] === 'cache.miss';
                })
            );

        $this->innerRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($customer);

        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with($cacheKey, $this->isType('callable'))
            ->willReturnCallback(static function ($key, $callback) use ($cacheItem) {
                return $callback($cacheItem);
            });

        $result = $this->repository->findByEmail($email);

        self::assertSame($customer, $result);
    }

    public function testCallProxiesToInnerRepositoryForDoctrineRepoMethods(): void
    {
        // Create fresh instances to avoid Psalm control flow analysis issues
        $innerRepository = $this->createMock(CustomerRepositoryInterface::class);
        $cache = $this->createMock(TagAwareCacheInterface::class);
        $cacheKeyBuilder = $this->createMock(CacheKeyBuilder::class);
        $logger = $this->createMock(LoggerInterface::class);

        // Create a test helper that implements the interface plus an extra method
        $innerRepo = new CustomerRepositoryTestHelper($innerRepository);

        $repository = new CachedCustomerRepository(
            $innerRepo,
            $cache,
            $cacheKeyBuilder,
            new CustomerCacheTagResolver($cacheKeyBuilder),
            $logger
        );

        // Call a method not in the interface - should be proxied via __call()
        $result = $repository->getClassName();

        self::assertSame(Customer::class, $result);
    }
}
