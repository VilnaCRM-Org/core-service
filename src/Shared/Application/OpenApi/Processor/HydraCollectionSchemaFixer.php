<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ArrayObject;

final class HydraCollectionSchemaFixer
{
    public function __construct(
        private HydraSchemaNormalizer $schemaNormalizer,
        private HydraViewExampleUpdater $viewExampleUpdater
    ) {
    }

    /**
     * @param ArrayObject<string, array|bool|float|int|string|ArrayObject|null> $schemas
     */
    public function apply(ArrayObject $schemas): ArrayObject
    {
        $schemasArray = $schemas->getArrayCopy();
        $normalized = $this->schemaNormalizer->normalize($schemasArray);
        $normalizedSchemas = $schemasArray;
        $hasChanges = false;

        foreach ($normalized as $schemaName => $schema) {
            $normalizedSchema = SchemaNormalizer::normalize($schema);
            $updatedSchema = $this->viewExampleUpdater->update($normalizedSchema);
            $schemaWasNormalized = array_key_exists($schemaName, $schemasArray)
                && SchemaNormalizer::normalize($schemasArray[$schemaName]) !== $normalizedSchema;

            if ($updatedSchema === null) {
                if (! $schemaWasNormalized) {
                    continue;
                }

                $updatedSchema = $normalizedSchema;
            }

            $normalizedSchemas[$schemaName] = new ArrayObject($updatedSchema);
            $hasChanges = true;
        }

        return $hasChanges ? new ArrayObject($normalizedSchemas) : $schemas;
    }

    /**
     * @param array<string, array|bool|float|int|string|ArrayObject|null> $schema
     *
     * @return array<string, array|bool|float|int|string|ArrayObject|null>|null
     */
    public function fixSchema(array $schema): ?array
    {
        return $this->viewExampleUpdater->update(
            SchemaNormalizer::normalize($schema)
        );
    }
}
