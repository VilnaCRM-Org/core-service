<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

/**
 * @phpstan-type SchemaValue array|bool|float|int|string|\ArrayObject|null
 */
final class HydraViewExampleUpdater
{
    public function __construct(
        private HydraAllOfUpdater $allOfUpdater
    ) {
    }

    /**
     * @param array<string, SchemaValue> $normalized
     *
     * @return array<string, SchemaValue>|null
     */
    public function update(array $normalized): ?array
    {
        if (! isset($normalized['allOf'])) {
            return null;
        }

        $allOf = SchemaNormalizer::normalize($normalized['allOf']);
        $updatedAllOf = $this->allOfUpdater->update($allOf);
        if ($updatedAllOf === null) {
            return null;
        }

        $normalized['allOf'] = $updatedAllOf;

        return $normalized;
    }
}
