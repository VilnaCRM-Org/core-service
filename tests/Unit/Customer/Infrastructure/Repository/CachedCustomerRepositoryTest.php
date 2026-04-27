<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Infrastructure\Repository;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Core\Customer\Infrastructure\Collection\CustomerCachePolicyCollection;
use App\Core\Customer\Infrastructure\Repository\CachedCustomerRepository;
use App\Core\Customer\Infrastructure\Resolver\CustomerCacheTagResolver;
use App\Shared\Application\Command\CacheInvalidationCommand;
use App\Shared\Application\CommandHandler\CacheInvalidationCommandHandler;
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
            $this->logger,
            new CustomerCachePolicyCollection()
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

    public function testFindFreshBypassesCacheAndDelegatesToInnerRepository(): void
    {
        $customerId = (string) $this->faker->ulid();
        $customer = $this->createMock(Customer::class);

        $this->cache->expects($this->never())->method('get');
        $this->cacheKeyBuilder->expects($this->never())->method('buildCustomerKey');

        $this->innerRepository
            ->expects($this->once())
            ->method('find')
            ->with($customerId, 0, null)
            ->willReturn($customer);

        $result = $this->repository->findFresh($customerId);

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
            ->with($cacheKey, $this->isType('callable'), 0.0)
            ->willReturn($customer);

        $result = $this->repository->findByEmail($email);

        self::assertSame($customer, $result);
    }

    public function testFindByEmailUsesBetaWhenLookupPolicyProvidesIt(): void
    {
        $email = 'test@example.com';
        $cacheKeyBuilder = new CacheKeyBuilder();
        $cacheKey = $cacheKeyBuilder->buildCustomerEmailKey($email);
        $customer = $this->createMock(Customer::class);
        $policies = $this->createMock(CustomerCachePolicyCollection::class);
        $lookupPolicy = $this->lookupPolicyWithBeta();
        $repository = $this->repositoryWithPolicies($cacheKeyBuilder, $policies);

        $this->expectLookupPolicyBeta($policies, $lookupPolicy);
        $this->expectCacheGetWithBeta($cacheKey, $customer);

        self::assertSame($customer, $repository->findByEmail($email));
    }

    public function testFindFallsBackToDatabaseOnCacheError(): void
    {
        $customerId = (string) $this->faker->ulid();
        $cacheKey = 'customer.' . $customerId;
        $customer = $this->createMock(Customer::class);

        $this->expectCustomerKey($customerId, $cacheKey);
        $this->expectCacheGetUnavailable();
        $this->expectCacheErrorLog($cacheKey);
        $this->expectFindById($customerId, $customer);

        $result = $this->repository->find($customerId);

        self::assertSame($customer, $result);
    }

    public function testFindByEmailFallsBackToDatabaseOnCacheError(): void
    {
        $email = 'test@example.com';
        $cacheKey = 'customer.email.hash123';
        $customer = $this->createMock(Customer::class);

        $this->expectCustomerEmailKey($email, $cacheKey);
        $this->expectCacheGetUnavailable();
        $this->expectCacheErrorLog($cacheKey);
        $this->expectFindByEmail($email, $customer);

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
            ->method('delete')
            ->with($customer);

        $this->cache
            ->expects($this->never())
            ->method('invalidateTags');

        $this->repository->deleteByEmail($email);
    }

    public function testDeleteByEmailInvalidatesFallbackTagsWhenCustomerLookupMisses(): void
    {
        $email = 'test@example.com';
        $emailHash = 'email_hash_123';

        $this->expectDeleteByEmailAfterLookupMiss($email);
        $this->expectEmailHash($email, $emailHash);
        $this->expectEmailFallbackInvalidation($emailHash);

        $this->repository->deleteByEmail($email);
    }

    public function testDeleteByEmailUsesSharedInvalidationHandlerForFallbackTags(): void
    {
        $email = 'test@example.com';
        $emailHash = (new CacheKeyBuilder())->hashEmail($email);
        $handler = $this->createMock(CacheInvalidationCommandHandler::class);
        $repository = $this->repositoryWithInvalidationHandler($handler);

        $this->expectDeleteByEmailAfterLookupMiss($email);
        $this->cache
            ->expects($this->never())
            ->method('invalidateTags');
        $handler
            ->expects($this->once())
            ->method('tryHandle')
            ->with($this->callback(
                static function (CacheInvalidationCommand $command) use ($emailHash): bool {
                    self::assertRepositoryFallbackCommand($command, $emailHash);

                    return true;
                }
            ))
            ->willReturn(true);

        $repository->deleteByEmail($email);
    }

    public function testDeleteByEmailFallsBackToDirectInvalidationWhenSharedHandlerReportsFailure(): void
    {
        $email = 'test@example.com';
        $emailHash = (new CacheKeyBuilder())->hashEmail($email);
        $handler = $this->createMock(CacheInvalidationCommandHandler::class);
        $repository = $this->repositoryWithInvalidationHandler($handler);

        $this->expectDeleteByEmailAfterLookupMiss($email);
        $handler
            ->expects($this->once())
            ->method('tryHandle')
            ->willReturn(false);
        $this->cache
            ->expects($this->once())
            ->method('invalidateTags')
            ->with([
                'customer',
                'customer.collection',
                'customer.email.' . $emailHash,
            ])
            ->willReturn(true);

        $repository->deleteByEmail($email);
    }

    public function testDeleteByEmailFallsBackToDirectInvalidationWhenSharedHandlerThrows(): void
    {
        $email = 'test@example.com';
        $emailHash = (new CacheKeyBuilder())->hashEmail($email);
        $handler = $this->createMock(CacheInvalidationCommandHandler::class);
        $repository = $this->repositoryWithInvalidationHandler($handler);

        $this->expectDeleteByEmailAfterLookupMiss($email);
        $handler
            ->expects($this->once())
            ->method('tryHandle')
            ->willThrowException(new \RuntimeException('handler unavailable'));
        $this->cache
            ->expects($this->once())
            ->method('invalidateTags')
            ->with([
                'customer',
                'customer.collection',
                'customer.email.' . $emailHash,
            ])
            ->willReturn(true);
        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'Shared cache invalidation failed after customer deletion',
                $this->callback(static function (array $context): bool {
                    self::assertSame('cache.invalidation.error', $context['operation']);
                    self::assertSame('repository_fallback', $context['source']);
                    self::assertSame('handler unavailable', $context['error']);
                    self::assertInstanceOf(\RuntimeException::class, $context['exception']);

                    return true;
                })
            );

        $repository->deleteByEmail($email);
    }

    public function testDeleteByIdUsesSharedInvalidationHandlerForFallbackTags(): void
    {
        $id = (string) $this->faker->ulid();
        $handler = $this->createMock(CacheInvalidationCommandHandler::class);
        $repository = $this->repositoryWithInvalidationHandler($handler);

        $this->expectDeleteByIdAfterLookupMiss($id);
        $this->cache
            ->expects($this->never())
            ->method('invalidateTags');
        $handler
            ->expects($this->once())
            ->method('tryHandle')
            ->with($this->isInstanceOf(CacheInvalidationCommand::class))
            ->willReturn(true);

        $repository->deleteById($id);
    }

    public function testDeleteByEmailStillDeletesWhenCustomerLookupFails(): void
    {
        $email = 'test@example.com';
        $emailHash = 'email_hash_123';

        $this->innerRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willThrowException(new \RuntimeException('Lookup failed'));

        $this->expectEmailLookupFailureWarning($emailHash);

        $this->innerRepository
            ->expects($this->once())
            ->method('deleteByEmail')
            ->with($email);

        $this->cacheKeyBuilder
            ->expects($this->exactly(2))
            ->method('hashEmail')
            ->with($email)
            ->willReturn($emailHash);

        $this->expectEmailFallbackInvalidation($emailHash);

        $this->repository->deleteByEmail($email);
    }

    public function testDeleteByEmailLogsWarningWhenCacheInvalidationFails(): void
    {
        $email = 'test@example.com';
        $emailHash = 'email_hash_123';

        $this->expectDeleteByEmailAfterLookupMiss($email);
        $this->expectEmailHash($email, $emailHash);

        $this->cache
            ->expects($this->once())
            ->method('invalidateTags')
            ->willThrowException(new \RuntimeException('Cache backend unavailable'));

        $this->expectDeletionInvalidationWarning('Cache backend unavailable');

        $this->repository->deleteByEmail($email);
    }

    public function testDeleteByEmailLogsWarningWhenCacheInvalidationReturnsFalse(): void
    {
        $email = 'test@example.com';
        $emailHash = 'email_hash_123';

        $this->expectDeleteByEmailAfterLookupMiss($email);
        $this->expectEmailHash($email, $emailHash);

        $this->cache
            ->expects($this->once())
            ->method('invalidateTags')
            ->willReturn(false);

        $this->expectDeletionInvalidationWarning('Tag invalidation returned false');

        $this->repository->deleteByEmail($email);
    }

    public function testDeleteByEmailLogsWarningWhenTagResolutionFails(): void
    {
        $email = 'test@example.com';

        $this->expectDeleteByEmailAfterLookupMiss($email);

        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('hashEmail')
            ->with($email)
            ->willThrowException(new \RuntimeException('Tag resolution failed'));

        $this->cache
            ->expects($this->never())
            ->method('invalidateTags');

        $this->expectDeletionInvalidationWarning('Tag resolution failed');

        $this->repository->deleteByEmail($email);
    }

    public function testDeleteByIdDelegatesToInnerRepository(): void
    {
        $id = (string) $this->faker->ulid();
        $email = 'test@example.com';
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
            ->method('delete')
            ->with($customer);

        $this->cache
            ->expects($this->never())
            ->method('invalidateTags');

        $this->repository->deleteById($id);
    }

    public function testDeleteByIdLogsWarningWhenCacheInvalidationFails(): void
    {
        $id = (string) $this->faker->ulid();

        $this->expectDeleteByIdAfterLookupMiss($id);

        $this->cache
            ->expects($this->once())
            ->method('invalidateTags')
            ->willThrowException(new \RuntimeException('Cache backend unavailable'));

        $this->expectDeletionInvalidationWarning('Cache backend unavailable');

        $this->repository->deleteById($id);
    }

    public function testDeleteByIdStillDeletesWhenCustomerLookupFails(): void
    {
        $id = (string) $this->faker->ulid();

        $this->innerRepository
            ->expects($this->once())
            ->method('find')
            ->with($id, 0, null)
            ->willThrowException(new \RuntimeException('Lookup failed'));

        $this->expectIdLookupFailureWarning($id);

        $this->innerRepository
            ->expects($this->once())
            ->method('deleteById')
            ->with($id);

        $this->expectIdFallbackInvalidation($id);

        $this->repository->deleteById($id);
    }

    public function testDeleteByIdInvalidatesFallbackTagsWhenCustomerLookupMisses(): void
    {
        $id = (string) $this->faker->ulid();

        $this->expectDeleteByIdAfterLookupMiss($id);
        $this->expectIdFallbackInvalidation($id);

        $this->repository->deleteById($id);
    }

    public function testFindCacheMissLoadsFromDatabase(): void
    {
        $customerId = (string) $this->faker->ulid();
        $cacheKey = 'customer.' . $customerId;
        $customer = $this->createMock(Customer::class);

        $cacheItem = $this->createMock(ItemInterface::class);

        $this->expectCustomerKey($customerId, $cacheKey);
        $this->expectCacheItemPolicy($cacheItem, 600, ['customer', 'customer.' . $customerId]);
        $this->expectCustomerCacheMissLog($cacheKey, $customerId);
        $this->expectFindById($customerId, $customer);
        $this->expectCacheMissLoad($cacheKey, $cacheItem, 1.0);

        $result = $this->repository->find($customerId);

        self::assertSame($customer, $result);
    }

    public function testFindByEmailCacheMissLoadsFromDatabase(): void
    {
        $email = 'test@example.com';
        $emailHash = 'hash_abc123';
        $cacheKey = 'customer.email.' . $emailHash;
        $customer = $this->createMock(Customer::class);

        $cacheItem = $this->createMock(ItemInterface::class);

        $this->expectCustomerEmailKey($email, $cacheKey);
        $this->expectEmailHash($email, $emailHash);
        $this->expectCacheItemPolicy(
            $cacheItem,
            300,
            ['customer', 'customer.email', 'customer.email.' . $emailHash]
        );
        $this->expectEmailCacheMissLog($cacheKey);
        $this->expectFindByEmail($email, $customer);
        $this->expectCacheMissLoad($cacheKey, $cacheItem, 0.0);

        $result = $this->repository->findByEmail($email);

        self::assertSame($customer, $result);
    }

    public function testFindByEmailCacheMissUsesNegativeLookupPolicyWhenCustomerIsMissing(): void
    {
        $email = 'missing@example.com';
        $emailHash = 'missing_hash';
        $cacheKey = 'customer.email.' . $emailHash;
        $cacheItem = $this->createMock(ItemInterface::class);

        $this->expectCustomerEmailKey($email, $cacheKey);
        $this->expectEmailHash($email, $emailHash);
        $this->expectCacheItemPolicy(
            $cacheItem,
            60,
            ['customer', 'customer.email', 'customer.email.' . $emailHash]
        );
        $this->expectEmailCacheMissLogWithFamily(
            $cacheKey,
            CustomerCachePolicyCollection::FAMILY_NEGATIVE_LOOKUP
        );
        $this->innerRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);
        $this->expectCacheMissLoad($cacheKey, $cacheItem, 0.0);

        self::assertNull($this->repository->findByEmail($email));
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
            $logger,
            new CustomerCachePolicyCollection()
        );

        // Call a method not in the interface - should be proxied via __call()
        $result = $repository->getClassName();

        self::assertSame(Customer::class, $result);
    }

    /**
     * @return array{
     *     context: string,
     *     family: string,
     *     ttl: int,
     *     beta: float,
     *     consistency: string,
     *     refresh_source: string,
     *     tags: list<string>
     * }
     */
    private function lookupPolicyWithBeta(): array
    {
        return [
            'context' => CustomerCachePolicyCollection::CONTEXT,
            'family' => CustomerCachePolicyCollection::FAMILY_LOOKUP,
            'ttl' => 300,
            'beta' => 0.5,
            'consistency' => 'eventual',
            'refresh_source' => CustomerCachePolicyCollection::REFRESH_SOURCE_REPOSITORY,
            'tags' => ['customer', 'customer.email'],
        ];
    }

    private function repositoryWithPolicies(
        CacheKeyBuilder $cacheKeyBuilder,
        CustomerCachePolicyCollection&MockObject $policies
    ): CachedCustomerRepository {
        /** @psalm-suppress NoValue PHPUnit can mock final classes in this suite. */
        return new CachedCustomerRepository(
            $this->innerRepository,
            $this->cache,
            $cacheKeyBuilder,
            new CustomerCacheTagResolver($cacheKeyBuilder),
            $this->logger,
            $policies
        );
    }

    private function repositoryWithInvalidationHandler(
        CacheInvalidationCommandHandler $handler
    ): CachedCustomerRepository {
        $cacheKeyBuilder = new CacheKeyBuilder();

        return new CachedCustomerRepository(
            $this->innerRepository,
            $this->cache,
            $cacheKeyBuilder,
            new CustomerCacheTagResolver($cacheKeyBuilder),
            $this->logger,
            new CustomerCachePolicyCollection(),
            $handler
        );
    }

    private static function assertRepositoryFallbackCommand(
        CacheInvalidationCommand $command,
        string $emailHash
    ): void {
        self::assertSame(CustomerCachePolicyCollection::CONTEXT, $command->context());
        self::assertSame('repository_fallback', $command->source());
        self::assertSame('deleted', $command->operation());
        self::assertSame([
            'customer',
            'customer.collection',
            'customer.email.' . $emailHash,
        ], iterator_to_array($command->tags()));
        self::assertCount(0, $command->refreshCommands());
    }

    /**
     * @param array<string, mixed> $lookupPolicy
     */
    private function expectLookupPolicyBeta(
        CustomerCachePolicyCollection&MockObject $policies,
        array $lookupPolicy
    ): void {
        $policies
            ->expects($this->once())
            ->method('lookup')
            ->willReturn($lookupPolicy);
        $policies
            ->expects($this->once())
            ->method('beta')
            ->with($lookupPolicy)
            ->willReturn(0.5);
    }

    private function expectCacheGetWithBeta(string $cacheKey, Customer $customer): void
    {
        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with($cacheKey, $this->isType('callable'), 0.5)
            ->willReturn($customer);
    }

    private function expectCustomerKey(string $customerId, string $cacheKey): void
    {
        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('buildCustomerKey')
            ->with($customerId)
            ->willReturn($cacheKey);
    }

    private function expectCustomerEmailKey(string $email, string $cacheKey): void
    {
        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('buildCustomerEmailKey')
            ->with($email)
            ->willReturn($cacheKey);
    }

    private function expectCacheGetUnavailable(): void
    {
        $this->cache
            ->expects($this->once())
            ->method('get')
            ->willThrowException(new \RuntimeException('Cache unavailable'));
    }

    private function expectCacheErrorLog(string $cacheKey): void
    {
        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Cache error - falling back to database',
                $this->callback(static function (array $context) use ($cacheKey): bool {
                    self::assertSame($cacheKey, $context['cache_key']);
                    self::assertSame('Cache unavailable', $context['error']);
                    self::assertSame('cache.error', $context['operation']);

                    return true;
                })
            );
    }

    private function expectFindById(string $customerId, Customer $customer): void
    {
        $this->innerRepository
            ->expects($this->once())
            ->method('find')
            ->with($customerId, 0, null)
            ->willReturn($customer);
    }

    private function expectFindByEmail(string $email, Customer $customer): void
    {
        $this->innerRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($customer);
    }

    private function expectDeleteByEmailAfterLookupMiss(string $email): void
    {
        $this->innerRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);

        $this->innerRepository
            ->expects($this->once())
            ->method('deleteByEmail')
            ->with($email);
    }

    private function expectEmailHash(string $email, string $emailHash): void
    {
        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('hashEmail')
            ->with($email)
            ->willReturn($emailHash);
    }

    private function expectEmailFallbackInvalidation(string $emailHash): void
    {
        $this->cache
            ->expects($this->once())
            ->method('invalidateTags')
            ->with([
                'customer',
                'customer.collection',
                'customer.email.' . $emailHash,
            ])
            ->willReturn(true);
    }

    private function expectEmailLookupFailureWarning(string $emailHash): void
    {
        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'Customer lookup failed before deleteByEmail',
                $this->callback(static function (array $context) use ($emailHash): bool {
                    self::assertSame('customer.delete.lookup_failed', $context['operation']);
                    self::assertSame($emailHash, $context['email_hash']);
                    self::assertSame('Lookup failed', $context['error']);

                    return true;
                })
            );
    }

    private function expectDeletionInvalidationWarning(string $error): void
    {
        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'Cache invalidation failed after customer deletion',
                $this->callback(static function (array $context) use ($error): bool {
                    self::assertSame('cache.invalidation.error', $context['operation']);
                    self::assertSame($error, $context['error']);

                    return true;
                })
            );
    }

    private function expectDeleteByIdAfterLookupMiss(string $id): void
    {
        $this->innerRepository
            ->expects($this->once())
            ->method('find')
            ->with($id, 0, null)
            ->willReturn(null);

        $this->innerRepository
            ->expects($this->once())
            ->method('deleteById')
            ->with($id);
    }

    private function expectIdLookupFailureWarning(string $id): void
    {
        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'Customer lookup failed before deleteById',
                $this->callback(static function (array $context) use ($id): bool {
                    self::assertSame('customer.delete.lookup_failed', $context['operation']);
                    self::assertSame(hash('sha256', $id), $context['customer_id_hash']);
                    self::assertSame('Lookup failed', $context['error']);

                    return true;
                })
            );
    }

    private function expectIdFallbackInvalidation(string $id): void
    {
        $this->cache
            ->expects($this->once())
            ->method('invalidateTags')
            ->with([
                'customer',
                'customer.collection',
                'customer.' . $id,
            ])
            ->willReturn(true);
    }

    /**
     * @param list<string> $tags
     */
    private function expectCacheItemPolicy(
        ItemInterface&MockObject $cacheItem,
        int $ttl,
        array $tags
    ): void {
        $cacheItem
            ->expects($this->once())
            ->method('expiresAfter')
            ->with($ttl);

        $cacheItem
            ->expects($this->once())
            ->method('tag')
            ->with($tags);
    }

    private function expectCustomerCacheMissLog(string $cacheKey, string $customerId): void
    {
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Cache miss - loading customer from database',
                $this->callback(
                    static function (array $context) use ($cacheKey, $customerId): bool {
                        self::assertSame($cacheKey, $context['cache_key']);
                        self::assertSame($customerId, $context['customer_id']);
                        self::assertSame('cache.miss', $context['operation']);

                        return true;
                    }
                )
            );
    }

    private function expectEmailCacheMissLog(string $cacheKey): void
    {
        $this->expectEmailCacheMissLogWithFamily(
            $cacheKey,
            CustomerCachePolicyCollection::FAMILY_LOOKUP
        );
    }

    private function expectEmailCacheMissLogWithFamily(string $cacheKey, string $family): void
    {
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Cache miss - loading customer by email',
                $this->callback(static function (array $context) use ($cacheKey, $family): bool {
                    self::assertSame($cacheKey, $context['cache_key']);
                    self::assertSame('cache.miss', $context['operation']);
                    self::assertSame($family, $context['family']);

                    return true;
                })
            );
    }

    private function expectCacheMissLoad(
        string $cacheKey,
        ItemInterface $cacheItem,
        ?float $beta
    ): void {
        $parameters = [$cacheKey, $this->isType('callable')];

        if ($beta !== null) {
            $parameters[] = $beta;
        }

        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with(...$parameters)
            ->willReturnCallback(
                static function (string $key, callable $callback) use ($cacheItem) {
                    return $callback($cacheItem);
                }
            );
    }
}
