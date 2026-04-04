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
        return match (true) {
            ! self::shouldRemoveItems($payload) => $payload,
            default => array_diff_key($payload, ['items' => true]),
        };
    }

    /**
     * @param array<string, SchemaValue> $payload
     */
    private static function shouldRemoveItems(array $payload): bool
    {
        return match (true) {
            ! self::isArrayPayload($payload) => false,
            ! array_key_exists('items', $payload) => false,
            default => $payload['items'] === null,
        };
    }

    /**
     * @param array<string, SchemaValue> $payload
     */
    private static function isArrayPayload(array $payload): bool
    {
        return in_array(
            'array',
            SchemaNormalizer::normalize($payload['type'] ?? null),
            true
        );
    }
}
