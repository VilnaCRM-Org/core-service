<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ArrayObject;

final class PayloadItemsRequirementChecker
{
    /**
     * @param array<string, array|bool|float|int|string|ArrayObject|null> $payload
     */
    public static function shouldAddItems(?array $payload): bool
    {
        return match (true) {
            ! self::isArrayPayload($payload) => false,
            default => ($payload['items'] ?? null) === null,
        };
    }

    /**
     * @param array<string, array|bool|float|int|string|ArrayObject|null> $payload
     */
    private static function isArrayPayload(?array $payload): bool
    {
        return in_array('array', self::types($payload), true);
    }

    /**
     * @param array<string, array|bool|float|int|string|ArrayObject|null> $payload
     *
     * @return array<int|string, array|bool|float|int|string|ArrayObject|null>
     */
    private static function types(?array $payload): array
    {
        if (! \is_array($payload)) {
            return [];
        }

        $type = $payload['type'] ?? [];

        return match (true) {
            \is_string($type) => [$type],
            default => SchemaNormalizer::normalize($type),
        };
    }
}
