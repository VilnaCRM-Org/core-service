<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Serializer;

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
     *
     * @param array<mixed>|string|int|float|bool|null $value
     */
    public function shouldRemove(string|int $key, array|string|int|float|bool|null $value): bool
    {
        return $value === null || $this->isRemovableEmptyArray($key, $value);
    }

    /**
     * @param array<mixed>|string|int|float|bool|null $value
     */
    private function isRemovableEmptyArray(string|int $key, array|string|int|float|bool|null $value): bool
    {
        return is_array($value)
            && $value === []
            && $this->emptyValueChecker->shouldRemoveEmptyArray($key);
    }
}
