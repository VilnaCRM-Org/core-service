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
        // Cache invalidation is best-effort: don't fail the business operation if cache is down
        try {
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
        } catch (\Throwable $e) {
            // Log cache error but allow the business operation to succeed
            $this->logger->error('Cache invalidation failed after customer deletion', [
                'customer_id' => $event->customerId(),
                'event_id' => $event->eventId(),
                'error' => $e->getMessage(),
                'operation' => 'cache.invalidation.error',
            ]);
        }
    }

    /**
     * @return array<class-string>
     */
    public function subscribedTo(): array
    {
        return [CustomerDeletedEvent::class];
    }
}
