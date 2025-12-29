<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\EventSubscriber;

use App\Core\Customer\Application\EventSubscriber\CustomerCreatedCacheInvalidationSubscriber;
use App\Core\Customer\Domain\Event\CustomerCreatedEvent;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\Shared\Infrastructure\Cache\CacheKeyBuilder;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class CustomerCreatedCacheInvalidationSubscriberTest extends UnitTestCase
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

        $this->subscriber = new CustomerCreatedCacheInvalidationSubscriber(
            $this->cache,
            $this->cacheKeyBuilder,
            $this->logger
        );
    }

    public function testSubscribedToReturnsCorrectEvents(): void
    {
        $subscribedEvents = $this->subscriber->subscribedTo();

        self::assertCount(1, $subscribedEvents);
        self::assertContains(CustomerCreatedEvent::class, $subscribedEvents);
    }

    public function testInvokeInvalidatesCacheWithCorrectTags(): void
    {
        $customerId = (string) $this->faker->ulid();
        $customerEmail = 'test@example.com';
        $emailHash = 'email_hash_123';

        $event = new CustomerCreatedEvent(
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
                'Cache invalidated after customer creation',
                $this->callback(static function ($context) {
                    // Note: customer_id removed from logs for PII compliance
                    return $context['operation'] === 'cache.invalidation'
                        && $context['reason'] === 'customer_created'
                        && isset($context['event_id']);
                })
            );

        ($this->subscriber)($event);
    }

    public function testInvokeThrowsExceptionWhenCacheInvalidationFails(): void
    {
        $customerId = (string) $this->faker->ulid();
        $customerEmail = 'test@example.com';
        $emailHash = 'email_hash_123';

        $event = new CustomerCreatedEvent(
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

        // Exception should propagate - cache invalidation is critical for data consistency
        $this->logger
            ->expects($this->never())
            ->method('info');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Redis connection failed');

        ($this->subscriber)($event);
    }
}
