<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventDispatcher;

final class SafeQueryKeyValidator
{
    private const SAFE_QUERY_KEY_PATTERN = '/^[^\[\]]+(?:\[[^\[\]]*\])*$/u';

    public function isSafe(string $rawKey): bool
    {
        $decodedKey = urldecode($rawKey);

        if ($decodedKey === '') {
            return false;
        }

        if (! mb_check_encoding($decodedKey, 'UTF-8')) {
            return false;
        }

        return preg_match(self::SAFE_QUERY_KEY_PATTERN, $decodedKey) === 1;
    }
}
