<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ArrayObject;

/**
 * @phpstan-type SchemaValue array|bool|float|int|string|\ArrayObject|null
 */
final class HydraAllOfItemUpdater
{
    public function __construct(
        private HydraAtTypeExampleUpdater $exampleUpdater
    ) {
    }

    public function update(ArrayObject|array $item): ?ArrayObject
    {
        $normalizedItem = SchemaNormalizer::normalize($item);
        $properties = $this->extractAndNormalizeProperties($normalizedItem);
        $viewSchema = $this->extractAndNormalizeView($properties);
        $example = $this->extractAndUpdateExample($viewSchema);

        if ($example === null) {
            return null;
        }

        $viewSchema['example'] = $example;
        $properties['view'] = new ArrayObject($viewSchema);
        $normalizedItem['properties'] = $properties;

        return new ArrayObject($normalizedItem);
    }

    /**
     * @param array<string, SchemaValue> $normalizedItem
     *
     * @return array<string, SchemaValue>
     */
    private function extractAndNormalizeProperties(array $normalizedItem): array
    {
        return self::normalizedEntry($normalizedItem, 'properties');
    }

    /**
     * @param array<string, SchemaValue> $properties
     *
     * @return array<string, SchemaValue>
     */
    private function extractAndNormalizeView(array $properties): array
    {
        return self::normalizedEntry($properties, 'view');
    }

    /**
     * @param array<string, SchemaValue> $viewSchema
     *
     * @return array<string, SchemaValue>|null
     */
    private function extractAndUpdateExample(array $viewSchema): ?array
    {
        return $this->exampleUpdater->update(
            self::normalizedEntry($viewSchema, 'example')
        );
    }

    /**
     * @param array<string, SchemaValue> $source
     *
     * @return array<string, SchemaValue>
     */
    private static function normalizedEntry(array $source, string $key): array
    {
        return SchemaNormalizer::normalize($source[$key] ?? null);
    }
}
