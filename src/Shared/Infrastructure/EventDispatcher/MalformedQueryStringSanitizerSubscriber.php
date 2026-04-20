<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventDispatcher;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class MalformedQueryStringSanitizerSubscriber implements EventSubscriberInterface
{
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

        if ('' === $queryString) {
            return;
        }

        $sanitizedQueryString = $this->sanitizeQueryString($queryString);

        if ($sanitizedQueryString === $queryString) {
            return;
        }

        $request->server->set('QUERY_STRING', $sanitizedQueryString);
        $request->query->replace($this->parseQueryString($sanitizedQueryString));
    }

    private function sanitizeQueryString(string $queryString): string
    {
        $sanitizedParts = [];

        foreach (explode('&', $queryString) as $part) {
            if ('' === $part || '=' === $part[0]) {
                continue;
            }

            [$rawKey] = explode('=', $part, 2);

            if (! $this->isSafeQueryKey($rawKey)) {
                continue;
            }

            $sanitizedParts[] = $part;
        }

        return implode('&', $sanitizedParts);
    }

    private function isSafeQueryKey(string $rawKey): bool
    {
        $decodedKey = urldecode(str_replace('%5B', '[', $rawKey));

        if ('' === $decodedKey) {
            return false;
        }

        if (! mb_check_encoding($decodedKey, 'UTF-8')) {
            return false;
        }

        return $this->hasBalancedBrackets($decodedKey);
    }

    private function hasBalancedBrackets(string $value): bool
    {
        $depth = 0;

        for ($i = 0, $length = strlen($value); $i < $length; ++$i) {
            $character = $value[$i];

            if ('[' === $character) {
                ++$depth;

                continue;
            }

            if (']' !== $character) {
                continue;
            }

            if (0 === $depth) {
                return false;
            }

            --$depth;
        }

        return 0 === $depth;
    }

    /**
     * @return array<string, mixed>
     */
    private function parseQueryString(string $queryString): array
    {
        if ('' === $queryString) {
            return [];
        }

        parse_str($queryString, $parameters);

        return $parameters;
    }
}
