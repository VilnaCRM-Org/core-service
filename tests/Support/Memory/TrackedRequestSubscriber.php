<?php

declare(strict_types=1);

namespace App\Tests\Support\Memory;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @psalm-suppress UnusedClass Wired through config/services_test.yaml.
 */
#[AsEventListener(event: KernelEvents::REQUEST, method: 'onKernelRequest')]
final readonly class TrackedRequestSubscriber
{
    public function __construct(
        private TrackedRequestHolder $trackedRequestHolder,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (! $event->isMainRequest()) {
            return;
        }

        $this->trackedRequestHolder->track($event->getRequest());
    }
}
