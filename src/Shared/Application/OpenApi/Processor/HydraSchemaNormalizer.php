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
        if (! array_key_exists(self::HYDRA_COLLECTION_SCHEMA, $schemas)) {
            $schemas[self::HYDRA_COLLECTION_SCHEMA] = null;
        }
        $normalized = SchemaNormalizer::normalize($schemas[self::HYDRA_COLLECTION_SCHEMA]);
        if (! array_key_exists('allOf', $normalized)) {
            $normalized['allOf'] = [];
        }
        $schemas[self::HYDRA_COLLECTION_SCHEMA] = $normalized;

        return $schemas;
    }
}
