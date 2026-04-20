<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventDispatcher;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class MalformedQueryStringSanitizerSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly QueryStringSanitizer $queryStringSanitizer,
    ) {
    }

    /**
     * @return array<string, array{0: string, 1: int}>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 2048],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (! $event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $queryString = (string) $request->server->get('QUERY_STRING', '');

        if ($queryString === '') {
            return;
        }

        $sanitizedQueryString = $this->queryStringSanitizer->sanitize($queryString);

        if ($sanitizedQueryString === $queryString) {
            return;
        }

        $request->server->set('QUERY_STRING', $sanitizedQueryString);
        parse_str($sanitizedQueryString, $parameters);

        $request->query->replace($parameters);
    }
}
