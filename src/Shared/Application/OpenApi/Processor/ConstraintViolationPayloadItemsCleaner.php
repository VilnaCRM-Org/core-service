<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ArrayObject;

/**
 * @phpstan-type SchemaValue array|bool|float|int|string|ArrayObject|null
 */
final class ConstraintViolationPayloadItemsCleaner
{
    /**
     * @param array<string, SchemaValue> $payload
     *
     * @return array<string, SchemaValue>
     */
    public static function clean(array $payload): array
    {
        return self::shouldRemoveItems($payload)
            ? array_diff_key($payload, ['items' => true])
            : $payload;
    }

    /**
     * @param array<string, SchemaValue> $payload
     */
    private static function shouldRemoveItems(array $payload): bool
    {
        return self::isArrayPayload($payload)
            && array_key_exists('items', $payload)
            && $payload['items'] === null;
    }

    /**
     * @param array<string, SchemaValue> $payload
     */
    private static function isArrayPayload(array $payload): bool
    {
        $type = $payload['type'] ?? null;
        $types = match (true) {
            \is_string($type) => [$type],
            default => SchemaNormalizer::normalize($type),
        };

        return in_array(
            'array',
            $types,
            true
        );
    }
}
