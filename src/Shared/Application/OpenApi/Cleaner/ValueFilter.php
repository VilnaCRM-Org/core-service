<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Cleaner;

/**
 * Determines if a value should be filtered out during data cleaning.
 */
final class ValueFilter
{
    public function __construct(
        private readonly EmptyArrayFilter $emptyValueChecker
    ) {
    }

    /**
     * Check if a value should be removed.
     */
    public function shouldRemove(string|int $key, array|string|int|float|bool|null $value): bool
    {
        return $value === null
            || $this->isRemovableEmptyArray($key, $value);
    }

    private function isRemovableEmptyArray(
        string|int $key,
        array|string|int|float|bool|null $value
    ): bool {
        return is_array($value)
            && $value === []
            && $this->emptyValueChecker->shouldRemoveEmptyArray($key);
    }
}
