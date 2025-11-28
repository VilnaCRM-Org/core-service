<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Cleaner;

/**
 * Determines if a value should be filtered out during data cleaning.
 */
final class ValueFilter
{
    public function __construct(
        private readonly EmptyValueChecker $emptyValueChecker
    ) {
    }

    /**
     * Check if a value should be removed.
     */
    public function shouldRemove(string|int $key, array|string|int|float|bool|null $value): bool
    {
        if ($value === null) {
            return true;
        }

        return $this->isRemovableEmptyArray($key, $value);
    }

    private function isRemovableEmptyArray(
        string|int $key,
        array|string|int|float|bool|null $value
    ): bool {
        if (!is_array($value)) {
            return false;
        }

        if ($value !== []) {
            return false;
        }

        return $this->emptyValueChecker->shouldRemoveEmptyArray($key);
    }
}
