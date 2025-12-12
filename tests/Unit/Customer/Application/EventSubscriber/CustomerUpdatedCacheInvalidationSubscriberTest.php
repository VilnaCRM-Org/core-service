<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\EventSubscriber;

use App\Core\Customer\Application\EventSubscriber\CustomerUpdatedCacheInvalidationSubscriber;
use App\Core\Customer\Domain\Event\CustomerUpdatedEvent;
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
    private CustomerUpdatedCacheInvalidationSubscriber $subscriber;

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

        $event = new CustomerUpdatedEvent(
            customerId: $customerId,
            currentEmail: $currentEmail,
            previousEmail: null
        );

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
            ]);

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Cache invalidated after customer update',
                $this->callback(static function ($context) use ($customerId) {
                    return $context['customer_id'] === $customerId
                        && $context['email_changed'] === false
                        && $context['operation'] === 'cache.invalidation'
                        && $context['reason'] === 'customer_updated';
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

        $event = new CustomerUpdatedEvent(
            customerId: $customerId,
            currentEmail: $currentEmail,
            previousEmail: $previousEmail
        );

        $this->cacheKeyBuilder
            ->expects($this->exactly(2))
            ->method('hashEmail')
            ->willReturnCallback(static function ($email) use ($previousEmail, $currentEmail, $previousEmailHash, $currentEmailHash) {
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
            ]);

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Cache invalidated after customer update',
                $this->callback(static function ($context) use ($customerId) {
                    return $context['customer_id'] === $customerId
                        && $context['email_changed'] === true
                        && $context['operation'] === 'cache.invalidation'
                        && $context['reason'] === 'customer_updated';
                })
            );

        ($this->subscriber)($event);
    }
}
