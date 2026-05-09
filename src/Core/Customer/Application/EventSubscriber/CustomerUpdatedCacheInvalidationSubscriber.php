<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\EventSubscriber;

use App\Core\Customer\Application\Factory\CustomerCacheRefreshCommandFactory;
use App\Core\Customer\Domain\Event\CustomerUpdatedEvent;
use App\Core\Customer\Infrastructure\Collection\CustomerCachePolicyCollection;
use App\Core\Customer\Infrastructure\Resolver\CustomerCacheInvalidationTagResolver;
use App\Shared\Application\Command\CacheInvalidationCommand;
use App\Shared\Application\CommandHandler\CacheInvalidationCommandHandler;
use App\Shared\Application\DTO\CacheInvalidationTagSet;
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
 * This subscriber runs in Symfony Messenger workers. Cache failures are logged
 * locally and kept best effort so domain event processing can continue.
 * We follow AP from CAP theorem (Availability + Partition tolerance over Consistency).
 */
final readonly class CustomerUpdatedCacheInvalidationSubscriber implements
    CustomerCacheInvalidationSubscriberInterface
{
    public function __construct(
        private TagAwareCacheInterface $cache,
        private CacheKeyBuilder $cacheKeyBuilder,
        private LoggerInterface $logger,
        private ?CustomerCacheInvalidationTagResolver $tagResolver = null,
        private ?CustomerCacheRefreshCommandFactory $refreshCommandFactory = null,
        private ?CacheInvalidationCommandHandler $invalidationHandler = null
    ) {
    }

    public function __invoke(CustomerUpdatedEvent $event): void
    {
        if ($this->tryInvalidateThroughHandler($event)) {
            return;
        }

        $this->tryInvalidateDirectly($event);
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

        $previousEmail = $event->previousEmail();
        if (
            $previousEmail !== null
            && strtolower($previousEmail) !== strtolower($event->currentEmail())
        ) {
            $tags[] = 'customer.email.' . $this->cacheKeyBuilder->hashEmail($previousEmail);
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

    private function logFailure(CustomerUpdatedEvent $event, string $error): void
    {
        $this->logger->warning('Cache invalidation failed after customer update', [
            'event_id' => $event->eventId(),
            'email_changed' => $event->emailChanged(),
            'operation' => 'cache.invalidation.error',
            'reason' => 'customer_updated',
            'error' => $error,
        ]);
    }

    private function invalidateThroughHandler(CustomerUpdatedEvent $event): bool
    {
        $tagResolver = $this->tagResolver;
        $refreshCommandFactory = $this->refreshCommandFactory;
        $invalidationHandler = $this->invalidationHandler;

        if (
            ! $tagResolver instanceof CustomerCacheInvalidationTagResolver
            || ! $refreshCommandFactory instanceof CustomerCacheRefreshCommandFactory
            || ! $invalidationHandler instanceof CacheInvalidationCommandHandler
        ) {
            return false;
        }

        return $this->handleSharedInvalidationResult(
            $event,
            $invalidationHandler->tryHandle(CacheInvalidationCommand::create(
                CustomerCachePolicyCollection::CONTEXT,
                'domain_event',
                'updated',
                $this->resolvedTagSet($event, $tagResolver),
                $refreshCommandFactory->createForUpdatedEvent($event)
            ))
        );
    }

    private function tryInvalidateThroughHandler(CustomerUpdatedEvent $event): bool
    {
        try {
            return $this->invalidateThroughHandler($event);
        } catch (\Throwable $e) {
            $this->logFailure($event, $e->getMessage());

            return false;
        }
    }

    private function handleSharedInvalidationResult(
        CustomerUpdatedEvent $event,
        bool $handled
    ): bool {
        if (! $handled) {
            $this->logFailure($event, 'Shared cache invalidation handler returned false');
        }

        return $handled;
    }

    private function resolvedTagSet(
        CustomerUpdatedEvent $event,
        CustomerCacheInvalidationTagResolver $tagResolver
    ): CacheInvalidationTagSet {
        $tags = $tagResolver->resolveForCustomerIdentifiers(
            $event->customerId(),
            $event->currentEmail(),
            $event->previousEmail()
        );

        return CacheInvalidationTagSet::create(...iterator_to_array($tags));
    }

    private function invalidateDirectly(CustomerUpdatedEvent $event): void
    {
        if ($this->cache->invalidateTags($this->buildTagsToInvalidate($event)) !== true) {
            $this->logFailure($event, 'Tag invalidation returned false');

            return;
        }

        $this->logSuccess($event);
    }

    private function tryInvalidateDirectly(CustomerUpdatedEvent $event): void
    {
        try {
            $this->invalidateDirectly($event);
        } catch (\Throwable $e) {
            $this->logFailure($event, $e->getMessage());
        }
    }
}
