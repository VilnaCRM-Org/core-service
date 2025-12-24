<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use Psr\Log\LoggerInterface;

/**
 * Resilient Domain Event Subscriber Decorator
 *
 * Wraps domain event subscribers with automatic error handling.
 * Ensures subscriber failures never break the application flow.
 *
 * Usage: Apply to non-critical subscribers (e.g., metrics)
 * DO NOT apply to critical subscribers (e.g., cache invalidation)
 */
final readonly class ResilientDomainEventSubscriberDecorator implements
    DomainEventSubscriberInterface
{
    public function __construct(
        private DomainEventSubscriberInterface $inner,
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(DomainEvent $event): void
    {
        try {
            ($this->inner)($event);
        } catch (\Throwable $exception) {
            $this->logger->error('Domain event subscriber execution failed', [
                'subscriber' => $this->inner::class,
                'event' => $event::eventName(),
                'event_id' => $event->eventId(),
                'error' => $exception->getMessage(),
                'exception_class' => $exception::class,
                'trace' => $exception->getTraceAsString(),
                'occurred_on' => $event->occurredOn(),
                'payload' => $event->toPrimitives(),
            ]);
            // Swallow exception - non-critical subscriber failure should not break flow
        }
    }

    /**
     * @return array<class-string>
     */
    public function subscribedTo(): array
    {
        return $this->inner->subscribedTo();
    }
}
