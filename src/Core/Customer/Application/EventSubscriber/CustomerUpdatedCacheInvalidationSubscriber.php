<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\EventSubscriber;

use App\Core\Customer\Domain\Event\CustomerUpdatedEvent;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\Shared\Infrastructure\Cache\CacheKeyBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * Customer Updated Event Cache Invalidation Subscriber
 *
 * Invalidates cache when a customer is updated
 * Handles email change edge case (both old and new email caches)
 */
final readonly class CustomerUpdatedCacheInvalidationSubscriber implements
    DomainEventSubscriberInterface
{
    public function __construct(
        private TagAwareCacheInterface $cache,
        private CacheKeyBuilder $cacheKeyBuilder,
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(CustomerUpdatedEvent $event): void
    {
        $tagsToInvalidate = [
            'customer.' . $event->customerId(),
            'customer.email.' . $this->cacheKeyBuilder->hashEmail($event->currentEmail()),
            'customer.collection',
        ];

        // If email changed, invalidate previous email cache too
        if ($event->emailChanged()) {
            $tagsToInvalidate[] = 'customer.email.' .
                $this->cacheKeyBuilder->hashEmail($event->previousEmail());
        }

        $this->cache->invalidateTags($tagsToInvalidate);

        $this->logger->info('Cache invalidated after customer update', [
            'customer_id' => $event->customerId(),
            'email_changed' => $event->emailChanged(),
            'event_id' => $event->eventId(),
            'operation' => 'cache.invalidation',
            'reason' => 'customer_updated',
        ]);
    }

    /**
     * @return array<class-string>
     */
    public function subscribedTo(): array
    {
        return [CustomerUpdatedEvent::class];
    }
}
