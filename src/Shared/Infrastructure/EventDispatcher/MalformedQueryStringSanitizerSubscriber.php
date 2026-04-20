<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventDispatcher;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\HeaderUtils;
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
    public static function getSubscribedEvents()
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
        $queryString = $request->server->get('QUERY_STRING', '');

        if (! is_string($queryString) || $queryString === '') {
            if (! is_string($queryString)) {
                $request->server->set('QUERY_STRING', '');
                $request->query->replace([]);
            }

            return;
        }

        $sanitizedQueryString = $this->queryStringSanitizer->sanitize($queryString);

        if ($sanitizedQueryString !== $queryString) {
            $request->server->set('QUERY_STRING', $sanitizedQueryString);
            $request->query->replace(HeaderUtils::parseQuery($sanitizedQueryString));
        }
    }
}
