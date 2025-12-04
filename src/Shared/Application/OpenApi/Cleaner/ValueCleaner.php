<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Cleaner;

/**
 * Cleans values by determining which should be removed during data cleaning.
 */
final class ValueCleaner
{
    public function __construct(
        private readonly EmptyArrayCleaner $emptyValueChecker
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
