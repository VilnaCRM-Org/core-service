<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Serializer;

use App\Shared\Application\OpenApi\Processor\SchemaNormalizer;
use ArrayObject;

final class HydraSchemaNormalizer
{
    private const HYDRA_COLLECTION_SCHEMA = 'HydraCollectionBaseSchema';

    /**
     * @param array<string, array|bool|float|int|string|ArrayObject|null> $schemas
     *
     * @return array<string, array|bool|float|int|string|ArrayObject|null>
     */
    public function normalize($schemas)
    {
        $schemas[self::HYDRA_COLLECTION_SCHEMA] = $this->normalizedHydraCollectionSchema($schemas);

        return $schemas;
    }

    /**
     * @param array<string, array|bool|float|int|string|ArrayObject|null> $schemas
     *
     * @return array<string, array|bool|float|int|string|ArrayObject|null>
     */
    private function normalizedHydraCollectionSchema($schemas)
    {
        $schema = SchemaNormalizer::normalize($schemas[self::HYDRA_COLLECTION_SCHEMA] ?? null);
        $schema['allOf'] = SchemaNormalizer::normalize($schema['allOf'] ?? null);

        return $schema;
    }
}
