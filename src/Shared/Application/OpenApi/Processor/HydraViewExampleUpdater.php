<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use App\Shared\Application\OpenApi\Updater\HydraDirectViewExampleUpdater;

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
        $updatedAllOf = $this->updateAllOf($updatedViewSchema ?? $normalized);

        return $updatedAllOf ?? $updatedViewSchema;
    }

    /**
     * @param array<string, SchemaValue> $normalized
     *
     * @return array<string, SchemaValue>|null
     */
    private function updateAllOf(array $normalized): ?array
    {
        if (! isset($normalized['allOf'])) {
            return null;
        }

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
