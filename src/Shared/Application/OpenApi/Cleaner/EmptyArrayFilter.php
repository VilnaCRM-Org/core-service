<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Cleaner;

/**
 * Filters which empty arrays should be removed from OpenAPI spec.
 */
final class EmptyArrayFilter
{
    private const REMOVABLE_EMPTY_KEYS = [
        'extensionProperties',
        'responses',
        'parameters',
        'examples',
        'requestBodies',
        'headers',
        'securitySchemes',
        'links',
        'callbacks',
        'pathItems',
    ];

    /**
     * Check if an empty array with this key should be removed.
     */
    public function shouldRemoveEmptyArray(string|int $key): bool
    {
        return is_string($key) && in_array($key, self::REMOVABLE_EMPTY_KEYS, true);
    }
}
