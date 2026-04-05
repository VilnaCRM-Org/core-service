<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ArrayObject;

/**
 * @phpstan-type SchemaValue array|bool|float|int|string|\ArrayObject|null
 */
final class HydraAllOfUpdater
{
    public function __construct(
        private HydraAllOfItemUpdater $itemUpdater
    ) {
    }

    /**
     * @param array<int, SchemaValue> $allOf
     *
     * @return array<int, SchemaValue>|null
     */
    public function update(array $allOf): ?array
    {
        $updatedAllOf = array_map(
            $this->updatedItem(...),
            $allOf
        );

        return $updatedAllOf === $allOf ? null : $updatedAllOf;
    }

    /**
     * @param SchemaValue $item
     *
     * @return SchemaValue
     */
    private function updatedItem(mixed $item): mixed
    {
        if (! self::isUpdatableItem($item)) {
            return $item;
        }

        return $this->itemUpdater->update($item) ?? $item;
    }

    private static function isUpdatableItem(mixed $item): bool
    {
        return is_array($item) || $item instanceof ArrayObject;
    }
}
