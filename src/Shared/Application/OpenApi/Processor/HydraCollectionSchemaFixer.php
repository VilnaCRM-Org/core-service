<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ArrayObject;

final class HydraCollectionSchemaFixer
{
    private const HYDRA_COLLECTION_SCHEMA = 'HydraCollectionBaseSchema';

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
        $hydraSchema = SchemaNormalizer::normalize(
            $normalized[self::HYDRA_COLLECTION_SCHEMA] ?? null
        );

        $updated = $this->viewExampleUpdater->update($hydraSchema);
        if ($updated === null) {
            return $schemas;
        }

        $normalizedSchemas[self::HYDRA_COLLECTION_SCHEMA] = new ArrayObject($updated);

        return new ArrayObject($normalizedSchemas);
    }
}
