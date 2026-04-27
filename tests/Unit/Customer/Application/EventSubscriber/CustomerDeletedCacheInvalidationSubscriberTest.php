<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\EventSubscriber;

use App\Core\Customer\Application\EventSubscriber\CustomerDeletedCacheInvalidationSubscriber;
use App\Core\Customer\Application\Factory\CustomerCacheRefreshCommandFactory;
use App\Core\Customer\Domain\Event\CustomerDeletedEvent;
use App\Core\Customer\Infrastructure\Collection\CustomerCachePolicyCollection;
use App\Core\Customer\Infrastructure\Resolver\CustomerCacheInvalidationTagResolver;
use App\Core\Customer\Infrastructure\Resolver\CustomerCacheRefreshTargetResolver;
use App\Shared\Application\Command\CacheInvalidationCommand;
use App\Shared\Application\CommandHandler\CacheInvalidationCommandHandler;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\Shared\Infrastructure\Cache\CacheKeyBuilder;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class CustomerDeletedCacheInvalidationSubscriberTest extends UnitTestCase
{
    private TagAwareCacheInterface&MockObject $cache;
    private CacheKeyBuilder&MockObject $cacheKeyBuilder;
    private LoggerInterface&MockObject $logger;
    private DomainEventSubscriberInterface $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = $this->createMock(TagAwareCacheInterface::class);
        $this->cacheKeyBuilder = $this->createMock(CacheKeyBuilder::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->subscriber = new CustomerDeletedCacheInvalidationSubscriber(
            $this->cache,
            $this->cacheKeyBuilder,
            $this->logger
        );
    }

    public function testSubscribedToReturnsCorrectEvents(): void
    {
        $subscribedEvents = $this->subscriber->subscribedTo();

        self::assertCount(1, $subscribedEvents);
        self::assertContains(CustomerDeletedEvent::class, $subscribedEvents);
    }

    public function testInvokeInvalidatesCacheWithCorrectTags(): void
    {
        $customerId = (string) $this->faker->ulid();
        $customerEmail = 'test@example.com';
        $emailHash = 'email_hash_123';

        $event = $this->event($customerId, $customerEmail);

        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('hashEmail')
            ->with($customerEmail)
            ->willReturn($emailHash);

        $this->cache
            ->expects($this->once())
            ->method('invalidateTags')
            ->with([
                'customer.' . $customerId,
                'customer.email.' . $emailHash,
                'customer.collection',
            ])
            ->willReturn(true);

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Cache invalidated after customer deletion',
                $this->callback(static function (array $context): bool {
                    self::assertInfoContext($context);

                    return true;
                })
            );

        ($this->subscriber)($event);
    }

    public function testInvokeUsesSharedInvalidationHandlerWhenAvailable(): void
    {
        $customerId = (string) $this->faker->ulid();
        $customerEmail = 'test@example.com';
        $handler = $this->createMock(CacheInvalidationCommandHandler::class);
        $subscriber = $this->subscriberWithHandler($handler);
        $event = $this->event($customerId, $customerEmail);

        $this->cache
            ->expects($this->never())
            ->method('invalidateTags');

        $handler
            ->expects($this->once())
            ->method('tryHandle')
            ->with($this->callback(
                static function (CacheInvalidationCommand $command) use ($customerId): bool {
                    self::assertSharedCommand($customerId, $command);

                    return true;
                }
            ))
            ->willReturn(true);

        $subscriber($event);
    }

    public function testInvokeFallsBackToDirectInvalidationWhenSharedHandlerThrows(): void
    {
        $customerId = (string) $this->faker->ulid();
        $customerEmail = 'test@example.com';
        $handler = $this->createMock(CacheInvalidationCommandHandler::class);
        $subscriber = $this->subscriberWithHandler($handler);
        $event = $this->event($customerId, $customerEmail);
        $emailHash = (new CacheKeyBuilder())->hashEmail($customerEmail);

        $handler
            ->expects($this->once())
            ->method('tryHandle')
            ->willThrowException(new \RuntimeException('handler unavailable'));
        $this->cache
            ->expects($this->once())
            ->method('invalidateTags')
            ->with([
                'customer.' . $customerId,
                'customer.email.' . $emailHash,
                'customer.collection',
            ])
            ->willReturn(true);
        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'Cache invalidation failed after customer deletion',
                $this->callback(static function (array $context): bool {
                    self::assertWarningContext('handler unavailable', $context);

                    return true;
                })
            );
        $this->logger
            ->expects($this->once())
            ->method('info');

        $subscriber($event);
    }

    public function testInvokeFallsBackToDirectInvalidationWhenSharedHandlerReportsFailure(): void
    {
        $customerId = (string) $this->faker->ulid();
        $customerEmail = 'test@example.com';
        $handler = $this->createMock(CacheInvalidationCommandHandler::class);
        $subscriber = $this->subscriberWithHandler($handler);
        $event = $this->event($customerId, $customerEmail);
        $emailHash = (new CacheKeyBuilder())->hashEmail($customerEmail);

        $handler
            ->expects($this->once())
            ->method('tryHandle')
            ->willReturn(false);
        $this->cache
            ->expects($this->once())
            ->method('invalidateTags')
            ->with([
                'customer.' . $customerId,
                'customer.email.' . $emailHash,
                'customer.collection',
            ])
            ->willReturn(true);
        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'Cache invalidation failed after customer deletion',
                $this->callback(static function (array $context): bool {
                    self::assertWarningContext(
                        'Shared cache invalidation handler returned false',
                        $context
                    );

                    return true;
                })
            );
        $this->logger
            ->expects($this->once())
            ->method('info');

        $subscriber($event);
    }

    public function testInvokeLogsErrorWhenCacheInvalidationFails(): void
    {
        $customerId = (string) $this->faker->ulid();
        $customerEmail = 'test@example.com';
        $emailHash = 'email_hash_123';

        $event = $this->event($customerId, $customerEmail);

        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('hashEmail')
            ->with($customerEmail)
            ->willReturn($emailHash);

        // Simulate cache failure
        $this->cache
            ->expects($this->once())
            ->method('invalidateTags')
            ->willThrowException(new \RuntimeException('Redis connection failed'));

        $this->logger
            ->expects($this->never())
            ->method('info');

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'Cache invalidation failed after customer deletion',
                $this->callback(static function (array $context): bool {
                    self::assertWarningContext('Redis connection failed', $context);

                    return true;
                })
            );

        ($this->subscriber)($event);
    }

    public function testInvokeLogsWarningWhenCacheInvalidationReturnsFalse(): void
    {
        $customerId = (string) $this->faker->ulid();
        $customerEmail = 'test@example.com';

        $event = $this->event($customerId, $customerEmail);

        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('hashEmail')
            ->with($customerEmail)
            ->willReturn('email_hash_123');

        $this->cache
            ->expects($this->once())
            ->method('invalidateTags')
            ->willReturn(false);

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'Cache invalidation failed after customer deletion',
                $this->callback(static function (array $context): bool {
                    self::assertWarningContext('Tag invalidation returned false', $context);

                    return true;
                })
            );

        ($this->subscriber)($event);
    }

    private function event(string $customerId, string $customerEmail): CustomerDeletedEvent
    {
        return new CustomerDeletedEvent(
            customerId: $customerId,
            customerEmail: $customerEmail
        );
    }

    private function subscriberWithHandler(
        CacheInvalidationCommandHandler $handler
    ): CustomerDeletedCacheInvalidationSubscriber {
        return new CustomerDeletedCacheInvalidationSubscriber(
            $this->cache,
            new CacheKeyBuilder(),
            $this->logger,
            new CustomerCacheInvalidationTagResolver(new CacheKeyBuilder()),
            new CustomerCacheRefreshCommandFactory(new CustomerCacheRefreshTargetResolver()),
            $handler
        );
    }

    private static function assertInfoContext(array $context): void
    {
        self::assertSame('cache.invalidation', $context['operation']);
        self::assertSame('customer_deleted', $context['reason']);
        self::assertArrayHasKey('event_id', $context);
    }

    private static function assertSharedCommand(
        string $customerId,
        CacheInvalidationCommand $command
    ): void {
        $refreshCommands = iterator_to_array($command->refreshCommands());

        self::assertSame(CustomerCachePolicyCollection::CONTEXT, $command->context());
        self::assertSame('domain_event', $command->source());
        self::assertSame('deleted', $command->operation());
        self::assertContains('customer.' . $customerId, iterator_to_array($command->tags()));
        self::assertCount(2, $refreshCommands);
        self::assertSame(
            CustomerCachePolicyCollection::REFRESH_SOURCE_INVALIDATE_ONLY,
            $refreshCommands[0]->refreshSource()
        );
    }

    private static function assertWarningContext(string $error, array $context): void
    {
        self::assertSame('cache.invalidation.error', $context['operation']);
        self::assertSame('customer_deleted', $context['reason']);
        self::assertSame($error, $context['error']);
    }
}
