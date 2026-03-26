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
        $normalizedSchemas = $schemas->getArrayCopy();
        $normalized = $this->schemaNormalizer->normalize($normalizedSchemas);
        $hasChanges = false;

        foreach ($normalized as $schemaName => $schema) {
            $updated = $this->viewExampleUpdater->update(
                SchemaNormalizer::normalize($schema)
            );
            if ($updated === null) {
                continue;
            }

            $normalizedSchemas[$schemaName] = new ArrayObject($updated);
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
