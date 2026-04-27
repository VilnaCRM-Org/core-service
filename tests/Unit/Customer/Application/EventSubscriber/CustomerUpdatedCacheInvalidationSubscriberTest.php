<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\EventSubscriber;

use App\Core\Customer\Application\EventSubscriber\CustomerUpdatedCacheInvalidationSubscriber;
use App\Core\Customer\Application\Factory\CustomerCacheRefreshCommandFactory;
use App\Core\Customer\Domain\Event\CustomerUpdatedEvent;
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

final class CustomerUpdatedCacheInvalidationSubscriberTest extends UnitTestCase
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

        $this->subscriber = new CustomerUpdatedCacheInvalidationSubscriber(
            $this->cache,
            $this->cacheKeyBuilder,
            $this->logger
        );
    }

    public function testSubscribedToReturnsCorrectEvents(): void
    {
        $subscribedEvents = $this->subscriber->subscribedTo();

        self::assertCount(1, $subscribedEvents);
        self::assertContains(CustomerUpdatedEvent::class, $subscribedEvents);
    }

    public function testInvokeInvalidatesCacheWithoutEmailChange(): void
    {
        $customerId = (string) $this->faker->ulid();
        $currentEmail = 'test@example.com';
        $emailHash = 'email_hash_123';

        $event = $this->event($customerId, $currentEmail, null);

        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('hashEmail')
            ->with($currentEmail)
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
                'Cache invalidated after customer update',
                $this->callback(static function (array $context): bool {
                    self::assertInfoContext(false, $context);

                    return true;
                })
            );

        ($this->subscriber)($event);
    }

    public function testInvokeInvalidatesCacheWithEmailChange(): void
    {
        $customerId = (string) $this->faker->ulid();
        $previousEmail = 'old@example.com';
        $currentEmail = 'new@example.com';
        $previousEmailHash = 'old_hash_123';
        $currentEmailHash = 'new_hash_456';

        $event = $this->event($customerId, $currentEmail, $previousEmail);

        $this->cacheKeyBuilder
            ->expects($this->exactly(2))
            ->method('hashEmail')
            ->willReturnCallback(static function (string $email) use (
                $previousEmail,
                $currentEmail,
                $previousEmailHash,
                $currentEmailHash
            ): string {
                return match ($email) {
                    $currentEmail => $currentEmailHash,
                    $previousEmail => $previousEmailHash,
                };
            });

        $this->cache
            ->expects($this->once())
            ->method('invalidateTags')
            ->with([
                'customer.' . $customerId,
                'customer.email.' . $currentEmailHash,
                'customer.collection',
                'customer.email.' . $previousEmailHash,
            ])
            ->willReturn(true);

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Cache invalidated after customer update',
                $this->callback(static function (array $context): bool {
                    self::assertInfoContext(true, $context);

                    return true;
                })
            );

        ($this->subscriber)($event);
    }

    public function testInvokeDoesNotDuplicateEmailTagForCaseOnlyEmailChange(): void
    {
        $customerId = (string) $this->faker->ulid();
        $currentEmail = 'same@example.com';
        $previousEmail = 'Same@Example.COM';
        $emailHash = 'same_hash_123';

        $event = $this->event($customerId, $currentEmail, $previousEmail);

        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('hashEmail')
            ->with($currentEmail)
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
            ->method('info');

        ($this->subscriber)($event);
    }

    public function testInvokeUsesSharedInvalidationHandlerWhenAvailable(): void
    {
        $customerId = (string) $this->faker->ulid();
        $previousEmail = 'old@example.com';
        $currentEmail = 'new@example.com';
        $handler = $this->createMock(CacheInvalidationCommandHandler::class);
        $subscriber = $this->subscriberWithHandler($handler);
        $event = $this->event($customerId, $currentEmail, $previousEmail);

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
        $currentEmail = 'test@example.com';
        $handler = $this->createMock(CacheInvalidationCommandHandler::class);
        $subscriber = $this->subscriberWithHandler($handler);
        $event = $this->event($customerId, $currentEmail, null);
        $emailHash = (new CacheKeyBuilder())->hashEmail($currentEmail);

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
                'Cache invalidation failed after customer update',
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
        $currentEmail = 'test@example.com';
        $handler = $this->createMock(CacheInvalidationCommandHandler::class);
        $subscriber = $this->subscriberWithHandler($handler);
        $event = $this->event($customerId, $currentEmail, null);
        $emailHash = (new CacheKeyBuilder())->hashEmail($currentEmail);

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
                'Cache invalidation failed after customer update',
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
        $currentEmail = 'test@example.com';
        $emailHash = 'email_hash_123';

        $event = $this->event($customerId, $currentEmail, null);

        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('hashEmail')
            ->with($currentEmail)
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
                'Cache invalidation failed after customer update',
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
        $currentEmail = 'test@example.com';

        $event = $this->event($customerId, $currentEmail, null);

        $this->cacheKeyBuilder
            ->expects($this->once())
            ->method('hashEmail')
            ->with($currentEmail)
            ->willReturn('email_hash_123');

        $this->cache
            ->expects($this->once())
            ->method('invalidateTags')
            ->willReturn(false);

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'Cache invalidation failed after customer update',
                $this->callback(static function (array $context): bool {
                    self::assertWarningContext('Tag invalidation returned false', $context);

                    return true;
                })
            );

        ($this->subscriber)($event);
    }

    private function event(
        string $customerId,
        string $currentEmail,
        ?string $previousEmail
    ): CustomerUpdatedEvent {
        return new CustomerUpdatedEvent(
            customerId: $customerId,
            currentEmail: $currentEmail,
            previousEmail: $previousEmail
        );
    }

    private function subscriberWithHandler(
        CacheInvalidationCommandHandler $handler
    ): CustomerUpdatedCacheInvalidationSubscriber {
        return new CustomerUpdatedCacheInvalidationSubscriber(
            $this->cache,
            new CacheKeyBuilder(),
            $this->logger,
            new CustomerCacheInvalidationTagResolver(new CacheKeyBuilder()),
            new CustomerCacheRefreshCommandFactory(new CustomerCacheRefreshTargetResolver()),
            $handler
        );
    }

    private static function assertInfoContext(bool $emailChanged, array $context): void
    {
        self::assertSame($emailChanged, $context['email_changed']);
        self::assertSame('cache.invalidation', $context['operation']);
        self::assertSame('customer_updated', $context['reason']);
        self::assertArrayHasKey('event_id', $context);
    }

    private static function assertSharedCommand(
        string $customerId,
        CacheInvalidationCommand $command
    ): void {
        self::assertSame(CustomerCachePolicyCollection::CONTEXT, $command->context());
        self::assertSame('domain_event', $command->source());
        self::assertSame('updated', $command->operation());
        self::assertContains('customer.' . $customerId, iterator_to_array($command->tags()));
        self::assertCount(3, $command->refreshCommands());
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function assertWarningContext(string $error, array $context): void
    {
        self::assertSame('cache.invalidation.error', $context['operation']);
        self::assertSame('customer_updated', $context['reason']);
        self::assertSame($error, $context['error']);
    }
}
