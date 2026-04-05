<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use App\Shared\Application\OpenApi\Serializer\HydraSchemaNormalizer;
use ArrayObject;

/**
 * @phpstan-type SchemaValue array|bool|float|int|string|ArrayObject|null
 */
final class HydraCollectionSchemasUpdater
{
    public function __construct(
        private HydraSchemaNormalizer $schemaNormalizer,
        private HydraViewExampleUpdater $viewExampleUpdater
    ) {
    }

    /**
     * @param ArrayObject<string, SchemaValue> $schemas
     */
    public function update(ArrayObject $schemas): ArrayObject
    {
        $schemasArray = $schemas->getArrayCopy();
        $normalizedSchemas = $this->schemaNormalizer->normalize($schemasArray);
        $hasChanges = false;

        foreach ($normalizedSchemas as $schemaName => $schema) {
            $updatedSchema = $this->resolvedSchemaCandidate(
                $schemasArray,
                (string) $schemaName,
                $schema
            );

            if ($updatedSchema === null) {
                continue;
            }

            $normalizedSchemas[$schemaName] = $this->wrappedUpdatedSchema($updatedSchema);
            $hasChanges = true;
        }

        return match ($hasChanges) {
            true => new ArrayObject($normalizedSchemas),
            default => $schemas,
        };
    }

    /**
     * @param array<string, SchemaValue> $schemas
     * @param SchemaValue $schema
     *
     * @return array<int|string, SchemaValue>|null
     */
    private function resolvedSchemaCandidate(
        array $schemas,
        string $schemaName,
        array|bool|float|int|string|ArrayObject|null $schema
    ): ?array {
        return HydraCollectionSchemaCandidateResolver::resolve(
            $schemas,
            $schemaName,
            SchemaNormalizer::normalize($schema),
            $this->viewExampleUpdater
        );
    }

    /**
     * @param array<int|string, SchemaValue> $schema
     */
    private function wrappedUpdatedSchema(array $schema): ArrayObject
    {
        return new ArrayObject($schema);
    }
}
