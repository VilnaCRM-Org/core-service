<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\CommandHandler;

use App\Core\Customer\Application\CommandHandler\CustomerCacheRefreshCommandHandler;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Core\Customer\Infrastructure\Collection\CustomerCachePolicyCollection;
use App\Shared\Application\Command\CacheRefreshCommand;
use App\Shared\Application\DTO\CacheRefreshPolicy;
use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Infrastructure\Cache\CacheKeyBuilder;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class CustomerCacheRefreshCommandHandlerTest extends UnitTestCase
{
    private CustomerRepositoryInterface&MockObject $repository;
    private TagAwareCacheInterface&MockObject $cache;
    private LoggerInterface&MockObject $logger;
    private CacheKeyBuilder $cacheKeyBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(CustomerRepositoryInterface::class);
        $this->cache = $this->createMock(TagAwareCacheInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->cacheKeyBuilder = new CacheKeyBuilder();
    }

    public function testContextReturnsCustomerContext(): void
    {
        $handler = $this->handler();

        self::assertSame(CustomerCachePolicyCollection::CONTEXT, $handler->context());
    }

    public function testSkipsRefreshWhenCachePoolIsUnavailable(): void
    {
        $command = $this->detailRefreshCommand((string) $this->faker->ulid());
        $handler = $this->handlerWithoutCache();

        $this->expectSkippedRefreshLog();

        $result = $handler($command);

        self::assertFalse($result->refreshed());
        self::assertSame('cache_unavailable', $result->reason());
    }

    public function testRefreshesCustomerDetailCacheFromFreshRepositoryRead(): void
    {
        $customerId = (string) $this->faker->ulid();
        $customer = $this->customer('detail@example.com');
        $command = $this->refreshCommand(
            CustomerCachePolicyCollection::FAMILY_DETAIL,
            'customer_id',
            $customerId
        );
        $cacheKey = $this->cacheKeyBuilder->buildCustomerKey($customerId);
        $item = $this->createMock(ItemInterface::class);

        $this->expectDetailCacheRefresh($cacheKey, $customerId, $item);
        $this->expectFreshCustomerRead($customerId, $customer);
        $this->expectRefreshLog(CustomerCachePolicyCollection::FAMILY_DETAIL, $command);

        $result = $this->handler()($command);

        $this->assertRefreshedResult(
            CustomerCachePolicyCollection::FAMILY_DETAIL,
            $command,
            $result
        );
    }

    public function testRefreshesCustomerLookupCacheFromEmailRepositoryRead(): void
    {
        $email = 'lookup@example.com';
        $customer = $this->customer($email);
        $command = $this->refreshCommand(
            CustomerCachePolicyCollection::FAMILY_LOOKUP,
            'email',
            $email
        );
        $cacheKey = $this->cacheKeyBuilder->buildCustomerEmailKey($email);
        $emailHash = $this->cacheKeyBuilder->hashEmail($email);
        $item = $this->createMock(ItemInterface::class);

        $this->expectLookupCacheRefresh($cacheKey, $emailHash, $item);
        $this->expectEmailCustomerRead($email, $customer);
        $this->expectRefreshLog(CustomerCachePolicyCollection::FAMILY_LOOKUP, $command);

        $result = $this->handler()($command);

        $this->assertRefreshedResult(
            CustomerCachePolicyCollection::FAMILY_LOOKUP,
            $command,
            $result
        );
    }

    public function testRefreshesMissingCustomerLookupWithNegativePolicy(): void
    {
        $email = 'missing-lookup@example.com';
        $command = $this->refreshCommand(
            CustomerCachePolicyCollection::FAMILY_LOOKUP,
            'email',
            $email
        );
        $cacheKey = $this->cacheKeyBuilder->buildCustomerEmailKey($email);
        $emailHash = $this->cacheKeyBuilder->hashEmail($email);
        $item = $this->createMock(ItemInterface::class);

        $this->expectNegativeLookupCacheRefresh($cacheKey, $emailHash, $item);
        $this->repository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);
        $this->expectRefreshLog(CustomerCachePolicyCollection::FAMILY_LOOKUP, $command);

        $result = $this->handler()($command);

        $this->assertRefreshedResult(
            CustomerCachePolicyCollection::FAMILY_LOOKUP,
            $command,
            $result
        );
    }

    public function testSkipsDetailRefreshWhenCustomerIdIsMissing(): void
    {
        $this->cache
            ->expects($this->never())
            ->method('delete');

        $result = $this->handler()($this->refreshCommand(
            CustomerCachePolicyCollection::FAMILY_DETAIL,
            'email',
            'wrong@example.com'
        ));

        self::assertFalse($result->refreshed());
        self::assertSame('missing_customer_id', $result->reason());
    }

    public function testSkipsLookupRefreshWhenEmailIsMissing(): void
    {
        $this->cache
            ->expects($this->never())
            ->method('delete');

        $result = $this->handler()($this->refreshCommand(
            CustomerCachePolicyCollection::FAMILY_LOOKUP,
            'customer_id',
            (string) $this->faker->ulid()
        ));

        self::assertFalse($result->refreshed());
        self::assertSame('missing_email', $result->reason());
    }

    public function testSkipsUnsupportedFamilies(): void
    {
        $this->cache
            ->expects($this->never())
            ->method('delete');

        $result = $this->handler()($this->refreshCommand(
            'unsupported',
            'customer_id',
            (string) $this->faker->ulid()
        ));

        self::assertFalse($result->refreshed());
        self::assertSame('unsupported_family', $result->reason());
    }

    public function testLogsAndRethrowsWhenCacheDeleteFails(): void
    {
        $customerId = (string) $this->faker->ulid();
        $command = $this->detailRefreshCommand($customerId);

        $this->cache
            ->expects($this->once())
            ->method('delete')
            ->willThrowException(new \RuntimeException('redis unavailable'));

        $this->expectRefreshFailureLog($command, 'redis unavailable');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('redis unavailable');

        $this->handler()($command);
    }

    public function testLogsAndRethrowsWhenDetailCacheDeleteReturnsFalse(): void
    {
        $customerId = (string) $this->faker->ulid();
        $command = $this->detailRefreshCommand($customerId);
        $cacheKey = $this->cacheKeyBuilder->buildCustomerKey($customerId);

        $this->expectCacheDeleteReturnsFalse($cacheKey);
        $this->expectRefreshFailureLog($command, sprintf(
            'Cache key "%s" could not be deleted before refresh.',
            $cacheKey
        ));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('could not be deleted before refresh');

        $this->handler()($command);
    }

    public function testLogsAndRethrowsWhenLookupCacheDeleteReturnsFalse(): void
    {
        $email = 'lookup@example.com';
        $command = $this->refreshCommand(
            CustomerCachePolicyCollection::FAMILY_LOOKUP,
            'email',
            $email
        );
        $cacheKey = $this->cacheKeyBuilder->buildCustomerEmailKey($email);

        $this->expectCacheDeleteReturnsFalse($cacheKey);
        $this->expectRefreshFailureLog(
            $command,
            sprintf('Cache key "%s" could not be deleted before refresh.', $cacheKey),
            CustomerCachePolicyCollection::FAMILY_LOOKUP
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('could not be deleted before refresh');

        $this->handler()($command);
    }

    public function testLogsAndRethrowsWhenRepositoryRefreshFails(): void
    {
        $customerId = (string) $this->faker->ulid();
        $command = $this->detailRefreshCommand($customerId);
        $cacheKey = $this->cacheKeyBuilder->buildCustomerKey($customerId);
        $item = $this->createMock(ItemInterface::class);

        $this->expectDetailCacheRefresh($cacheKey, $customerId, $item);
        $this->expectFreshCustomerReadFails($customerId);
        $this->expectRefreshFailureLog($command, 'repository unavailable');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('repository unavailable');

        $this->handler()($command);
    }

    private function handler(): CustomerCacheRefreshCommandHandler
    {
        return new CustomerCacheRefreshCommandHandler(
            $this->repository,
            $this->cacheKeyBuilder,
            new CustomerCachePolicyCollection(),
            $this->logger,
            $this->cache
        );
    }

    private function handlerWithoutCache(): CustomerCacheRefreshCommandHandler
    {
        return new CustomerCacheRefreshCommandHandler(
            $this->repository,
            $this->cacheKeyBuilder,
            new CustomerCachePolicyCollection(),
            $this->logger
        );
    }

    private function detailRefreshCommand(string $customerId): CacheRefreshCommand
    {
        return $this->refreshCommand(
            CustomerCachePolicyCollection::FAMILY_DETAIL,
            'customer_id',
            $customerId
        );
    }

    private function expectSkippedRefreshLog(): void
    {
        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'Customer cache refresh skipped without cache pool',
                $this->callback(static function (array $context): bool {
                    self::assertSame('cache.refresh.skipped', $context['operation']);
                    self::assertSame(CustomerCachePolicyCollection::FAMILY_DETAIL, $context['family']);

                    return true;
                })
            );
    }

    private function expectDetailCacheRefresh(
        string $cacheKey,
        string $customerId,
        ItemInterface&MockObject $item
    ): void {
        $this->expectCacheItemPolicy($item, 600, ['customer', 'customer.' . $customerId]);

        $this->cache
            ->expects($this->once())
            ->method('delete')
            ->with($cacheKey)
            ->willReturn(true);
        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with($cacheKey, $this->isType('callable'), 1.0)
            ->willReturnCallback($this->returnCachedCustomer($item));
    }

    private function expectLookupCacheRefresh(
        string $cacheKey,
        string $emailHash,
        ItemInterface&MockObject $item
    ): void {
        $this->expectCacheItemPolicy(
            $item,
            300,
            ['customer', 'customer.email', 'customer.email.' . $emailHash]
        );

        $this->cache
            ->expects($this->once())
            ->method('delete')
            ->with($cacheKey)
            ->willReturn(true);
        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with($cacheKey, $this->isType('callable'), 0.0)
            ->willReturnCallback($this->returnCachedCustomer($item));
    }

    private function expectNegativeLookupCacheRefresh(
        string $cacheKey,
        string $emailHash,
        ItemInterface&MockObject $item
    ): void {
        $this->expectCacheItemPolicy(
            $item,
            60,
            ['customer', 'customer.email', 'customer.email.' . $emailHash]
        );

        $this->cache
            ->expects($this->once())
            ->method('delete')
            ->with($cacheKey)
            ->willReturn(true);
        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with($cacheKey, $this->isType('callable'), 0.0)
            ->willReturnCallback($this->returnCachedCustomer($item));
    }

    /**
     * @param list<string> $tags
     */
    private function expectCacheItemPolicy(
        ItemInterface&MockObject $item,
        int $ttl,
        array $tags
    ): void {
        $item
            ->expects($this->once())
            ->method('expiresAfter')
            ->with($ttl)
            ->willReturnSelf();
        $item
            ->expects($this->once())
            ->method('tag')
            ->with($tags)
            ->willReturnSelf();
    }

    private function expectFreshCustomerRead(string $customerId, Customer $customer): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findFresh')
            ->with($customerId)
            ->willReturn($customer);
    }

    private function expectEmailCustomerRead(string $email, Customer $customer): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($customer);
    }

    private function expectFreshCustomerReadFails(string $customerId): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findFresh')
            ->with($customerId)
            ->willThrowException(new \RuntimeException('repository unavailable'));
    }

    private function expectRefreshFailureLog(
        CacheRefreshCommand $command,
        string $error,
        string $family = CustomerCachePolicyCollection::FAMILY_DETAIL
    ): void {
        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'Customer cache refresh failed',
                $this->callback(static function (array $context) use ($command, $error, $family): bool {
                    self::assertRefreshErrorContext($command, $error, $family, $context);

                    return true;
                })
            );
    }

    private function expectCacheDeleteReturnsFalse(string $cacheKey): void
    {
        $this->cache
            ->expects($this->once())
            ->method('delete')
            ->with($cacheKey)
            ->willReturn(false);
        $this->cache
            ->expects($this->never())
            ->method('get');
    }

    private function expectRefreshLog(string $family, CacheRefreshCommand $command): void
    {
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Customer cache refreshed',
                $this->callback(static function (array $context) use ($family, $command): bool {
                    self::assertSame('cache.refresh', $context['operation']);
                    self::assertSame(CustomerCachePolicyCollection::CONTEXT, $context['context']);
                    self::assertSame($family, $context['family']);
                    self::assertSame($command->dedupeKey(), $context['dedupe_key']);

                    return true;
                })
            );
    }

    /**
     * @return callable(string, callable): ?Customer
     */
    private function returnCachedCustomer(ItemInterface $item): callable
    {
        return static function (string $key, callable $callback) use ($item): ?Customer {
            return $callback($item);
        };
    }

    private function assertRefreshedResult(
        string $family,
        CacheRefreshCommand $command,
        object $result
    ): void {
        self::assertTrue($result->refreshed());
        self::assertSame(CustomerCachePolicyCollection::CONTEXT, $result->context());
        self::assertSame($family, $result->family());
        self::assertSame($command->dedupeKey(), $result->dedupeKey());
    }

    /**
     * @param array{operation: string, family: string, dedupe_key: string, error: string} $context
     */
    private static function assertRefreshErrorContext(
        CacheRefreshCommand $command,
        string $error,
        string $family,
        array $context
    ): void {
        self::assertSame('cache.refresh.error', $context['operation']);
        self::assertSame($family, $context['family']);
        self::assertSame($command->dedupeKey(), $context['dedupe_key']);
        self::assertSame($error, $context['error']);
    }

    private function refreshCommand(
        string $family,
        string $identifierName,
        string $identifierValue
    ): CacheRefreshCommand {
        return CacheRefreshCommand::create(
            CustomerCachePolicyCollection::CONTEXT,
            $family,
            $identifierName,
            $identifierValue,
            CacheRefreshPolicy::SOURCE_REPOSITORY_REFRESH,
            'test',
            (string) $this->faker->ulid(),
            (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
        );
    }

    private function customer(string $email): Customer
    {
        return new Customer(
            initials: 'Test Customer',
            email: $email,
            phone: '+1234567890',
            leadSource: 'test',
            type: new CustomerType('business', new Ulid((string) $this->faker->ulid())),
            status: new CustomerStatus('active', new Ulid((string) $this->faker->ulid())),
            confirmed: true,
            ulid: new Ulid((string) $this->faker->ulid())
        );
    }
}
