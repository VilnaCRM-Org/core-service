<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ArrayObject;

/**
 * @phpstan-type SchemaValue array|bool|float|int|string|ArrayObject|null
 */
final class HydraCollectionSchemaCandidateResolver
{
    /**
     * @param array<string, SchemaValue> $schemas
     * @param array<int|string, SchemaValue> $normalizedSchema
     *
     * @return array<int|string, SchemaValue>|null
     */
    public static function resolve(
        $schemas,
        string $schemaName,
        $normalizedSchema,
        HydraViewExampleUpdater $viewExampleUpdater
    ) {
        $updatedSchema = $viewExampleUpdater->update($normalizedSchema);

        return match (true) {
            $updatedSchema !== null => $updatedSchema,
            self::existingSchema(
                $schemas,
                $schemaName,
                $normalizedSchema
            ) === $normalizedSchema => null,
            default => $normalizedSchema,
        };
    }

    /**
     * @param array<string, SchemaValue> $schemas
     * @param array<int|string, SchemaValue> $normalizedSchema
     *
     * @return array<int|string, SchemaValue>
     */
    private static function existingSchema(
        $schemas,
        string $schemaName,
        $normalizedSchema
    ) {
        return match (true) {
            array_key_exists($schemaName, $schemas) => SchemaNormalizer::normalize(
                $schemas[$schemaName]
            ),
            default => $normalizedSchema,
        };
    }
}
