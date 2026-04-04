<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ArrayObject;

final class HydraSchemaNormalizer
{
    private const HYDRA_COLLECTION_SCHEMA = 'HydraCollectionBaseSchema';

    /**
     * @param array<string, array|bool|float|int|string|ArrayObject|null> $schemas
     *
     * @return array<string, array|bool|float|int|string|ArrayObject|null>
     */
    public function normalize(array $schemas): array
    {
        $schemas[self::HYDRA_COLLECTION_SCHEMA] = $this->normalizedHydraCollectionSchema($schemas);

        return $schemas;
    }

    /**
     * @param array<string, array|bool|float|int|string|ArrayObject|null> $schemas
     *
     * @return array<string, array|bool|float|int|string|ArrayObject|null>
     */
    private function normalizedHydraCollectionSchema(array $schemas): array
    {
        $schema = SchemaNormalizer::normalize($schemas[self::HYDRA_COLLECTION_SCHEMA] ?? null);

        if (! is_array($schema['allOf'] ?? null)) {
            $schema['allOf'] = [];
        }

        return $schema;
    }
}
