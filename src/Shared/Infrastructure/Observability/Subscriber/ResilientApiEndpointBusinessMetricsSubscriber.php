<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\Subscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

final readonly class ResilientApiEndpointBusinessMetricsSubscriber implements
    EventSubscriberInterface
{
    public function __construct(
        private ApiEndpointBusinessMetricsSubscriber $decorated,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return ApiEndpointBusinessMetricsSubscriber::getSubscribedEvents();
    }

    public function onResponse(ResponseEvent $event): void
    {
        try {
            $this->decorated->onResponse($event);
        } catch (\Throwable $exception) {
            $request = $event->getRequest();

            $this->logger->error('Failed to emit endpoint metrics', [
                'path' => $request->getPathInfo(),
                'method' => $request->getMethod(),
                'error' => $exception->getMessage(),
                'exception_class' => $exception::class,
            ]);
        }
    }
}
