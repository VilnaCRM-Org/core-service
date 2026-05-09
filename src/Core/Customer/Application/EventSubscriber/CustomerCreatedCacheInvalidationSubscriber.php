<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\EventSubscriber;

use App\Core\Customer\Application\Factory\CustomerCacheRefreshCommandFactory;
use App\Core\Customer\Domain\Event\CustomerCreatedEvent;
use App\Core\Customer\Infrastructure\Collection\CustomerCachePolicyCollection;
use App\Core\Customer\Infrastructure\Resolver\CustomerCacheInvalidationTagResolver;
use App\Shared\Application\Command\CacheInvalidationCommand;
use App\Shared\Application\CommandHandler\CacheInvalidationCommandHandler;
use App\Shared\Application\DTO\CacheInvalidationTagSet;
use App\Shared\Infrastructure\Cache\CacheKeyBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * Customer Created Event Cache Invalidation Subscriber
 *
 * Invalidates cache when a customer is created.
 *
 * ARCHITECTURAL DECISION: Processed via async queue (ResilientAsyncEventBus)
 * This subscriber runs in Symfony Messenger workers. Cache failures are logged
 * locally and kept best effort so domain event processing can continue.
 * We follow AP from CAP theorem (Availability + Partition tolerance over Consistency).
 */
final readonly class CustomerCreatedCacheInvalidationSubscriber implements
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

    public function __invoke(CustomerCreatedEvent $event): void
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
        return [CustomerCreatedEvent::class];
    }

    /**
     * @return list<string>
     */
    private function resolveTags(CustomerCreatedEvent $event): array
    {
        return [
            'customer.' . $event->customerId(),
            'customer.email.' . $this->cacheKeyBuilder->hashEmail($event->customerEmail()),
            'customer.collection',
        ];
    }

    private function logFailure(CustomerCreatedEvent $event, string $error): void
    {
        $this->logger->warning('Cache invalidation failed after customer creation', [
            'event_id' => $event->eventId(),
            'operation' => 'cache.invalidation.error',
            'reason' => 'customer_created',
            'error' => $error,
        ]);
    }

    private function invalidateThroughHandler(CustomerCreatedEvent $event): bool
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
                'created',
                $this->resolvedTagSet($event, $tagResolver),
                $refreshCommandFactory->createForCreatedEvent($event)
            ))
        );
    }

    private function tryInvalidateThroughHandler(CustomerCreatedEvent $event): bool
    {
        try {
            return $this->invalidateThroughHandler($event);
        } catch (\Throwable $e) {
            $this->logFailure($event, $e->getMessage());

            return false;
        }
    }

    private function handleSharedInvalidationResult(
        CustomerCreatedEvent $event,
        bool $handled
    ): bool {
        if (! $handled) {
            $this->logFailure($event, 'Shared cache invalidation handler returned false');
        }

        return $handled;
    }

    private function resolvedTagSet(
        CustomerCreatedEvent $event,
        CustomerCacheInvalidationTagResolver $tagResolver
    ): CacheInvalidationTagSet {
        $tags = $tagResolver->resolveForCustomerIdentifiers(
            $event->customerId(),
            $event->customerEmail()
        );

        return CacheInvalidationTagSet::create(...iterator_to_array($tags));
    }

    private function invalidateDirectly(CustomerCreatedEvent $event): void
    {
        if ($this->cache->invalidateTags($this->resolveTags($event)) !== true) {
            $this->logFailure($event, 'Tag invalidation returned false');

            return;
        }

        $this->logger->info('Cache invalidated after customer creation', [
            'event_id' => $event->eventId(),
            'operation' => 'cache.invalidation',
            'reason' => 'customer_created',
        ]);
    }

    private function tryInvalidateDirectly(CustomerCreatedEvent $event): void
    {
        try {
            $this->invalidateDirectly($event);
        } catch (\Throwable $e) {
            $this->logFailure($event, $e->getMessage());
        }
    }
}
