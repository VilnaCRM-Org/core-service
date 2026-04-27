<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\EventSubscriber;

use App\Core\Customer\Application\Factory\CustomerCacheRefreshCommandFactory;
use App\Core\Customer\Domain\Event\CustomerDeletedEvent;
use App\Core\Customer\Infrastructure\Collection\CustomerCachePolicyCollection;
use App\Core\Customer\Infrastructure\Resolver\CustomerCacheInvalidationTagResolver;
use App\Shared\Application\Command\CacheInvalidationCommand;
use App\Shared\Application\CommandHandler\CacheInvalidationCommandHandler;
use App\Shared\Application\DTO\CacheInvalidationTagSet;
use App\Shared\Infrastructure\Cache\CacheKeyBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * Customer Deleted Event Cache Invalidation Subscriber
 *
 * Invalidates cache when a customer is deleted.
 *
 * ARCHITECTURAL DECISION: Processed via async queue (ResilientAsyncEventBus)
 * This subscriber runs in Symfony Messenger workers. Cache failures are logged
 * locally and kept best effort so domain event processing can continue.
 * We follow AP from CAP theorem (Availability + Partition tolerance over Consistency).
 */
final readonly class CustomerDeletedCacheInvalidationSubscriber implements
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

    public function __invoke(CustomerDeletedEvent $event): void
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
        return [CustomerDeletedEvent::class];
    }

    /**
     * @return list<string>
     */
    private function resolveTags(CustomerDeletedEvent $event): array
    {
        return [
            'customer.' . $event->customerId(),
            'customer.email.' . $this->cacheKeyBuilder->hashEmail($event->customerEmail()),
            'customer.collection',
        ];
    }

    private function logFailure(CustomerDeletedEvent $event, string $error): void
    {
        $this->logger->warning('Cache invalidation failed after customer deletion', [
            'event_id' => $event->eventId(),
            'operation' => 'cache.invalidation.error',
            'reason' => 'customer_deleted',
            'error' => $error,
        ]);
    }

    private function invalidateThroughHandler(CustomerDeletedEvent $event): bool
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
                'deleted',
                $this->resolvedTagSet($event, $tagResolver),
                $refreshCommandFactory->createForDeletedEvent($event)
            ))
        );
    }

    private function tryInvalidateThroughHandler(CustomerDeletedEvent $event): bool
    {
        try {
            return $this->invalidateThroughHandler($event);
        } catch (\Throwable $e) {
            $this->logFailure($event, $e->getMessage());

            return false;
        }
    }

    private function handleSharedInvalidationResult(
        CustomerDeletedEvent $event,
        bool $handled
    ): bool {
        if (! $handled) {
            $this->logFailure($event, 'Shared cache invalidation handler returned false');
        }

        return $handled;
    }

    private function resolvedTagSet(
        CustomerDeletedEvent $event,
        CustomerCacheInvalidationTagResolver $tagResolver
    ): CacheInvalidationTagSet {
        $tags = $tagResolver->resolveForCustomerIdentifiers(
            $event->customerId(),
            $event->customerEmail()
        );

        return CacheInvalidationTagSet::create(...iterator_to_array($tags));
    }

    private function invalidateDirectly(CustomerDeletedEvent $event): void
    {
        if ($this->cache->invalidateTags($this->resolveTags($event)) !== true) {
            $this->logFailure($event, 'Tag invalidation returned false');

            return;
        }

        $this->logger->info('Cache invalidated after customer deletion', [
            'event_id' => $event->eventId(),
            'operation' => 'cache.invalidation',
            'reason' => 'customer_deleted',
        ]);
    }

    private function tryInvalidateDirectly(CustomerDeletedEvent $event): void
    {
        try {
            $this->invalidateDirectly($event);
        } catch (\Throwable $e) {
            $this->logFailure($event, $e->getMessage());
        }
    }
}
