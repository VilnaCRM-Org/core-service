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
        $normalizedSchemas += [self::ULID_SCHEMA => null];
        $normalized = SchemaNormalizer::normalize($normalizedSchemas[self::ULID_SCHEMA]);
        if ($normalized === []) {
            return $schemas;
        }

        $normalized += ['description' => '', 'deprecated' => false];
        $normalizedSchemas[self::ULID_SCHEMA] = new ArrayObject([
            'type' => 'string',
            'description' => $normalized['description'],
            'deprecated' => $normalized['deprecated'],
        ]);

        return new ArrayObject($normalizedSchemas);
    }
}
