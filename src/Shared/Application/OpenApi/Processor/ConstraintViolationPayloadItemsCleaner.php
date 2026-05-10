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
    public function clean(array $payload): array
    {
        return $this->shouldRemoveItems($payload)
            ? array_diff_key($payload, ['items' => true])
            : $payload;
    }

    /**
     * @param array<string, SchemaValue> $payload
     */
    private function shouldRemoveItems(array $payload): bool
    {
        return $this->isArrayPayload($payload)
            && array_key_exists('items', $payload)
            && $payload['items'] === null;
    }

    /**
     * @param array<string, SchemaValue> $payload
     */
    private function isArrayPayload(array $payload): bool
    {
        $type = $payload['type'] ?? null;
        $types = match (true) {
            \is_string($type) => [$type],
            default => (new SchemaNormalizer())->normalize($type),
        };

        return in_array(
            'array',
            $types,
            true
        );
    }
}
