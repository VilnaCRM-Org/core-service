<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ArrayObject;

final class UlidSchemaFixer
{
    private const ULID_SCHEMA = 'UlidInterface.jsonld-output';

    /**
     * @param ArrayObject<string, array|bool|float|int|string|ArrayObject|null> $schemas
     */
    public function apply(ArrayObject $schemas): ArrayObject
    {
        $normalizedSchemas = $schemas->getArrayCopy();
        $schema = $normalizedSchemas[self::ULID_SCHEMA] ?? null;
        $normalized = SchemaNormalizer::normalize($schema);
        if ($normalized === []) {
            return $schemas;
        }

        $normalizedSchemas[self::ULID_SCHEMA] = new ArrayObject([
            'type' => 'string',
            'description' => $normalized['description'] ?? '',
            'deprecated' => $normalized['deprecated'] ?? false,
        ]);

        return new ArrayObject($normalizedSchemas);
    }
}
