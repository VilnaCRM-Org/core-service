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
    public function resolve(
        array $schemas,
        string $schemaName,
        array $normalizedSchema,
        HydraViewExampleUpdater $viewExampleUpdater
    ): ?array {
        $updatedSchema = $viewExampleUpdater->update($normalizedSchema);

        return match (true) {
            $updatedSchema !== null => $updatedSchema,
            $this->existingSchema(
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
    private function existingSchema(
        array $schemas,
        string $schemaName,
        array $normalizedSchema
    ): array {
        return match (true) {
            array_key_exists($schemaName, $schemas) => (new SchemaNormalizer())->normalize(
                $schemas[$schemaName]
            ),
            default => $normalizedSchema,
        };
    }
}
