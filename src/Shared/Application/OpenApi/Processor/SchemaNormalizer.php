<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ArrayObject;

/**
 * @phpstan-type SchemaValue array|bool|float|int|object|string|null
 */
final class SchemaNormalizer
{
    /**
     * @param SchemaValue $schema
     *
     * @return array<int|string, SchemaValue>
     */
    public static function normalize(object|array|string|int|float|bool|null $schema): array
    {
        return match (true) {
            $schema instanceof ArrayObject => $schema->getArrayCopy(),
            \is_array($schema) => $schema,
            default => [],
        };
    }
}
