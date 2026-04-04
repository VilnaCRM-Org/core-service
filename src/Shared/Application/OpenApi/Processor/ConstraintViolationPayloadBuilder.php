<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ArrayObject;

/**
 * @phpstan-type SchemaValue array|bool|float|int|string|ArrayObject|null
 */
final class ConstraintViolationPayloadBuilder
{
    /**
     * @param array<string, SchemaValue> $properties
     *
     * @return array<string, SchemaValue>|null
     */
    public static function build(array $properties): ?array
    {
        $payload = ConstraintViolationPayloadItemsCleaner::clean(
            SchemaNormalizer::normalize($properties['payload'] ?? ['type' => 'array'])
        );

        return match (true) {
            ! PayloadItemsRequirementChecker::shouldAddItems($payload) => null,
            default => ['items' => ['type' => 'object']] + $payload,
        };
    }
}
