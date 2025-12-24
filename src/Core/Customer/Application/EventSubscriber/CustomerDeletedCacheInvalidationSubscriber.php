<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\EventSubscriber;

use App\Core\Customer\Domain\Event\CustomerDeletedEvent;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\Shared\Infrastructure\Cache\CacheKeyBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * Customer Deleted Event Cache Invalidation Subscriber
 *
 * Invalidates cache when a customer is deleted
 *
 * ARCHITECTURAL DECISION: NOT wrapped with ResilientDomainEventSubscriberDecorator
 * Cache invalidation is critical for data consistency. If invalidation fails,
 * the request MUST fail to prevent serving stale data. Unlike metrics (observability),
 * cache failures directly impact correctness and user experience.
 */
final readonly class CustomerDeletedCacheInvalidationSubscriber implements
    DomainEventSubscriberInterface
{
    public function __construct(
        private TagAwareCacheInterface $cache,
        private CacheKeyBuilder $cacheKeyBuilder,
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(CustomerDeletedEvent $event): void
    {
        $this->cache->invalidateTags([
            'customer.' . $event->customerId(),
            'customer.email.' . $this->cacheKeyBuilder->hashEmail($event->customerEmail()),
            'customer.collection',
        ]);

        $this->logger->info('Cache invalidated after customer deletion', [
            'customer_id' => $event->customerId(),
            'event_id' => $event->eventId(),
            'operation' => 'cache.invalidation',
            'reason' => 'customer_deleted',
        ]);
    }

    /**
     * @return array<class-string>
     */
    public function subscribedTo(): array
    {
        return [CustomerDeletedEvent::class];
    }
}
