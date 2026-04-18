<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventListener;

final class ApiQueryKeyValidator
{
    private const SAFE_QUERY_KEY_PATTERN = '/^[A-Za-z0-9_.:-]+$/';

    public function allows(int|string $key, bool $allowIntegerKeys): bool
    {
        return match (true) {
            is_int($key) => $allowIntegerKeys,
            default => preg_match(self::SAFE_QUERY_KEY_PATTERN, $key) === 1,
        };
    }
}
