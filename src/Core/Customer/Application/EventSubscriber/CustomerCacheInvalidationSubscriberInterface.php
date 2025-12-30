<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\EventSubscriber;

use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;

/**
 * Marker interface for customer cache invalidation subscribers.
 *
 * Used to auto-bind the customer cache pool via Symfony _instanceof configuration.
 */
interface CustomerCacheInvalidationSubscriberInterface extends DomainEventSubscriberInterface
{
}
