<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\EventSubscriber;

use App\Core\Customer\Domain\Event\CustomerUpdatedEvent;
use App\Shared\Infrastructure\Cache\CacheKeyBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * Customer Updated Event Cache Invalidation Subscriber
 *
 * Invalidates cache when a customer is updated.
 * Handles email change edge case (both old and new email caches).
 *
 * ARCHITECTURAL DECISION: Processed via async queue (ResilientAsyncEventBus)
 * This subscriber runs in Symfony Messenger workers. Exceptions propagate to
 * DomainEventMessageHandler which catches, logs, and emits failure metrics.
 * We follow AP from CAP theorem (Availability + Partition tolerance over Consistency).
 */
final readonly class CustomerUpdatedCacheInvalidationSubscriber implements
    CustomerCacheInvalidationSubscriberInterface
{
    public function __construct(
        private TagAwareCacheInterface $cache,
        private CacheKeyBuilder $cacheKeyBuilder,
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(CustomerUpdatedEvent $event): void
    {
        $tagsToInvalidate = $this->buildTagsToInvalidate($event);
        $this->cache->invalidateTags($tagsToInvalidate);
        $this->logSuccess($event);
    }

    /**
     * @return array<class-string>
     */
    public function subscribedTo(): array
    {
        return [CustomerUpdatedEvent::class];
    }

    /**
     * @return array<string>
     */
    private function buildTagsToInvalidate(CustomerUpdatedEvent $event): array
    {
        $tags = [
            'customer.' . $event->customerId(),
            'customer.email.' . $this->cacheKeyBuilder->hashEmail($event->currentEmail()),
            'customer.collection',
        ];

        // If email changed, invalidate previous email cache too
        if ($event->emailChanged() && $event->previousEmail() !== null) {
            $tags[] = 'customer.email.' .
                $this->cacheKeyBuilder->hashEmail($event->previousEmail());
        }

        return $tags;
    }

    private function logSuccess(CustomerUpdatedEvent $event): void
    {
        $this->logger->info('Cache invalidated after customer update', [
            'event_id' => $event->eventId(),
            'email_changed' => $event->emailChanged(),
            'operation' => 'cache.invalidation',
            'reason' => 'customer_updated',
        ]);
    }
}
