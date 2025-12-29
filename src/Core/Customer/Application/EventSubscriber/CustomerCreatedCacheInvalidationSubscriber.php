<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\EventSubscriber;

use App\Core\Customer\Domain\Event\CustomerCreatedEvent;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\Shared\Infrastructure\Cache\CacheKeyBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * Customer Created Event Cache Invalidation Subscriber
 *
 * Invalidates cache when a customer is created.
 *
 * ARCHITECTURAL DECISION: Processed via async queue (ResilientAsyncEventBus)
 * This subscriber runs in Symfony Messenger workers, wrapped with Layer 2 resilience.
 * Any failures are caught, logged, and emit metrics - never thrown.
 * We follow AP from CAP theorem (Availability + Partition tolerance over Consistency).
 */
final readonly class CustomerCreatedCacheInvalidationSubscriber implements
    DomainEventSubscriberInterface
{
    public function __construct(
        private TagAwareCacheInterface $cache,
        private CacheKeyBuilder $cacheKeyBuilder,
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(CustomerCreatedEvent $event): void
    {
        $this->cache->invalidateTags([
            'customer.' . $event->customerId(),
            'customer.email.' . $this->cacheKeyBuilder->hashEmail($event->customerEmail()),
            'customer.collection',
        ]);

        $this->logger->info('Cache invalidated after customer creation', [
            'event_id' => $event->eventId(),
            'operation' => 'cache.invalidation',
            'reason' => 'customer_created',
        ]);
    }

    /**
     * @return array<class-string>
     */
    public function subscribedTo(): array
    {
        return [CustomerCreatedEvent::class];
    }
}
