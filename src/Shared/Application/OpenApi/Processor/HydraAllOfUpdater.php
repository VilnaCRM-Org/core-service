<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ArrayObject;

final class HydraAllOfUpdater
{
    public function __construct(
        private HydraAllOfItemUpdater $itemUpdater
    ) {
    }

    /**
     * @param array<int, mixed> $allOf
     *
     * @return array<int, mixed>|null
     */
    public function update(array $allOf): ?array
    {
        foreach ($allOf as $index => $item) {
            if (! is_array($item) && ! $item instanceof ArrayObject) {
                continue;
            }

            $updatedItem = $this->itemUpdater->update($item);
            if ($updatedItem === null) {
                continue;
            }

            $allOf[$index] = $updatedItem;

            return $allOf;
        }

        return null;
    }
}
