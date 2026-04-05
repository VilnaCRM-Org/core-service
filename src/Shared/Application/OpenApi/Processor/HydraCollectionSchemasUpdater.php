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
            $updatedSchema = HydraCollectionSchemaCandidateResolver::resolve(
                $schemasArray,
                (string) $schemaName,
                SchemaNormalizer::normalize($schema),
                $this->viewExampleUpdater
            );

            if ($updatedSchema === null) {
                continue;
            }

            $normalizedSchemas[$schemaName] = new ArrayObject($updatedSchema);
            $hasChanges = true;
        }

        return match ($hasChanges) {
            true => new ArrayObject($normalizedSchemas),
            default => $schemas,
        };
    }
}
