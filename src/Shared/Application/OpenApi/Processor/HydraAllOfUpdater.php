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
        $validItems = array_filter(
            $allOf,
            static fn ($item): bool => is_array($item) || $item instanceof ArrayObject
        );

        $hasChanges = false;
        foreach ($validItems as $index => $item) {
            $updatedItem = $this->itemUpdater->update($item);
            if ($updatedItem !== null) {
                $allOf[$index] = $updatedItem;
                $hasChanges = true;
            }
        }

        return $hasChanges ? $allOf : null;
    }
}
