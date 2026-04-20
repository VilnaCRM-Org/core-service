<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventDispatcher;

final class QueryStringSanitizer
{
    public function __construct(
        private readonly SafeQueryKeyValidator $safeQueryKeyValidator,
    ) {
    }

    public function sanitize(string $queryString): string
    {
        $sanitizedParts = [];

        foreach (explode('&', $queryString) as $part) {
            if ($part === '' || $part[0] === '=') {
                continue;
            }

            [$rawKey] = explode('=', $part, 2);

            if (! $this->safeQueryKeyValidator->isSafe($rawKey)) {
                continue;
            }

            $sanitizedParts[] = $part;
        }

        return implode('&', $sanitizedParts);
    }
}
