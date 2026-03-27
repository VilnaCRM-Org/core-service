<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

/**
 * @phpstan-type SchemaValue array|bool|float|int|string|\ArrayObject|null
 */
final class HydraViewExampleUpdater
{
    public function __construct(
        private HydraAllOfUpdater $allOfUpdater,
        private HydraDirectViewExampleUpdater $directViewExampleUpdater
    ) {
    }

    /**
     * @param array<string, SchemaValue> $normalized
     *
     * @return array<string, SchemaValue>|null
     */
    public function update(array $normalized): ?array
    {
        $updatedViewSchema = $this->directViewExampleUpdater->update($normalized);
        if ($updatedViewSchema !== null || ! isset($normalized['allOf'])) {
            return $updatedViewSchema;
        }

        return $this->updateAllOf($normalized);
    }

    /**
     * @param array<string, SchemaValue> $normalized
     *
     * @return array<string, SchemaValue>|null
     */
    private function updateAllOf(array $normalized): ?array
    {
        $updatedAllOf = $this->allOfUpdater->update(
            SchemaNormalizer::normalize($normalized['allOf'])
        );
        if ($updatedAllOf === null) {
            return null;
        }

        $normalized['allOf'] = $updatedAllOf;

        return $normalized;
    }
}
