<?php

declare(strict_types=1);

namespace App\Tests\Support\Memory;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @psalm-suppress UnusedClass Wired through config/services_test.yaml.
 */
final readonly class TrackedRequestSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private TrackedRequestHolder $trackedRequestHolder,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (! $event->isMainRequest()) {
            return;
        }

        $this->trackedRequestHolder->track($event->getRequest());
    }
}
