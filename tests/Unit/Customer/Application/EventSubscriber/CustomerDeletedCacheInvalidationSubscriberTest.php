<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\EventSubscriber;

use App\Core\Customer\Application\EventSubscriber\CustomerDeletedCacheInvalidationSubscriber;
use App\Core\Customer\Domain\Event\CustomerDeletedEvent;
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

        $event = new CustomerDeletedEvent(
            customerId: $customerId,
            customerEmail: $customerEmail
        );

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
            ]);

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Cache invalidated after customer deletion',
                $this->callback(static function ($context) {
                    // Note: customer_id removed from logs for PII compliance
                    return $context['operation'] === 'cache.invalidation'
                        && $context['reason'] === 'customer_deleted'
                        && isset($context['event_id']);
                })
            );

        ($this->subscriber)($event);
    }

    public function testInvokeLogsErrorWhenCacheInvalidationFails(): void
    {
        $customerId = (string) $this->faker->ulid();
        $customerEmail = 'test@example.com';
        $emailHash = 'email_hash_123';

        $event = new CustomerDeletedEvent(
            customerId: $customerId,
            customerEmail: $customerEmail
        );

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

        // Should log error, not info
        $this->logger
            ->expects($this->never())
            ->method('info');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Redis connection failed');

        ($this->subscriber)($event);
    }
}
