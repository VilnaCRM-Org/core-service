<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventDispatcher;

final class SafeQueryKeyValidator
{
    private const SAFE_QUERY_KEY_PATTERN = '/^[^\[\]]+(?:\[[^\[\]]*\])*$/';

    public function isSafe(string $rawKey): bool
    {
        $decodedKey = urldecode($rawKey);

        return $decodedKey !== ''
            && mb_check_encoding($decodedKey, 'UTF-8')
            && preg_match(self::SAFE_QUERY_KEY_PATTERN, $decodedKey) === 1;
    }
}
