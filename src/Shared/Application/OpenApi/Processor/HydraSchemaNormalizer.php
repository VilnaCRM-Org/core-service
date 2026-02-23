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
     * @return array<string, array|bool|float|int|string|ArrayObject|null>|null
     */
    public function normalize(array $schemas): ?array
    {
        $schema = $schemas[self::HYDRA_COLLECTION_SCHEMA] ?? null;
        $normalized = SchemaNormalizer::normalize($schema);

        return is_array($normalized['allOf'] ?? null) ? $normalized : null;
    }
}
