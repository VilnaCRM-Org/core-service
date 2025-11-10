<?php

declare(strict_types=1);

namespace App\Shared\Application\Service;

/**
 * Resolves string field values with validation against empty/whitespace-only values.
 * Prevents GraphQL mutations from overwriting existing values with blank strings.
 */
final readonly class StringFieldResolver
{
    /**
     * Returns the new value if it has valid content (not null, empty, or whitespace-only),
     * otherwise returns the default value.
     */
    public function resolve(?string $newValue, string $defaultValue): string
    {
        return $this->hasValidContent($newValue) ? $newValue : $defaultValue;
    }

    /**
     * Checks if a string value has valid content (not null, empty, or whitespace-only).
     */
    public function hasValidContent(?string $value): bool
    {
        if ($value === null) {
            return false;
        }

        return strlen(trim($value)) > 0;
    }
}
