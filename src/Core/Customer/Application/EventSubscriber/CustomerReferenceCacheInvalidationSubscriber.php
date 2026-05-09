<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\EventSubscriber;

use App\Core\Customer\Domain\Event\CustomerStatusCreatedEvent;
use App\Core\Customer\Domain\Event\CustomerStatusUpdatedEvent;
use App\Core\Customer\Domain\Event\CustomerTypeCreatedEvent;
use App\Core\Customer\Domain\Event\CustomerTypeUpdatedEvent;
use App\Core\Customer\Infrastructure\Collection\CustomerCacheInvalidationRuleCollection;
use App\Core\Customer\Infrastructure\Collection\CustomerCachePolicyCollection;
use App\Shared\Application\CommandHandler\CacheInvalidationCommandHandler;
use App\Shared\Application\DTO\CacheInvalidationTagSet;
use App\Shared\Application\EventSubscriber\AbstractCacheInvalidationSubscriber;
use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Infrastructure\Collection\CacheRefreshCommandCollection;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Invalidates customer collection/reference caches after reference data events.
 */
final readonly class CustomerReferenceCacheInvalidationSubscriber extends
    AbstractCacheInvalidationSubscriber
{
    public function __construct(
        CacheInvalidationCommandHandler $handler,
        LoggerInterface $logger
    ) {
        parent::__construct($handler, $logger);
    }

    public function __invoke(DomainEvent $event): void
    {
        $this->invalidate(
            CustomerCachePolicyCollection::CONTEXT,
            $this->operationFor($event),
            CacheInvalidationTagSet::create(
                'customer.collection',
                'customer.reference'
            ),
            CacheRefreshCommandCollection::create()
        );
    }

    /**
     * @return array<class-string>
     */
    public function subscribedTo(): array
    {
        return [
            CustomerStatusCreatedEvent::class,
            CustomerStatusUpdatedEvent::class,
            CustomerTypeCreatedEvent::class,
            CustomerTypeUpdatedEvent::class,
        ];
    }

    private function operationFor(DomainEvent $event): string
    {
        return match ($event::class) {
            CustomerStatusCreatedEvent::class,
            CustomerTypeCreatedEvent::class => CustomerCacheInvalidationRuleCollection::OPERATION_CREATED,
            CustomerStatusUpdatedEvent::class,
            CustomerTypeUpdatedEvent::class => CustomerCacheInvalidationRuleCollection::OPERATION_UPDATED,
            default => throw new InvalidArgumentException(sprintf(
                'Unsupported customer reference event "%s".',
                $event::class
            )),
        };
    }
}
