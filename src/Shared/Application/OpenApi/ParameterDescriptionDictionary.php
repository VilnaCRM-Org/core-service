<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi;

/**
 * Provides the shared dictionary of query parameter descriptions used across
 * OpenAPI processors and augmenters. Keeping the values in a single place avoids
 * duplicating large switch blocks across multiple classes.
 */
final class ParameterDescriptionDictionary
{
    private const DESCRIPTIONS = [
        // Ordering
        'order[ulid]' => 'Sort by customer unique identifier',
        'order[createdAt]' => 'Sort by creation date',
        'order[updatedAt]' => 'Sort by last update date',
        'order[email]' => 'Sort by customer email address',
        'order[initials]' => 'Sort by customer initials',
        'order[phone]' => 'Sort by customer phone number',
        'order[leadSource]' => 'Sort by lead source',
        'order[type.value]' => 'Sort by customer type',
        'order[status.value]' => 'Sort by customer status',
        'order[value]' => 'Sort by value',

        // Generic filters
        'initials' => 'Filter by customer initials (exact match)',
        'initials[]' => 'Filter by multiple customer initials (exact match)',
        'email' => 'Filter by customer email address (exact match)',
        'email[]' => 'Filter by multiple customer email addresses (exact match)',
        'phone' => 'Filter by customer phone number (exact match)',
        'phone[]' => 'Filter by multiple customer phone numbers (exact match)',
        'leadSource' => 'Filter by lead source (exact match)',
        'leadSource[]' => 'Filter by multiple lead sources (exact match)',
        'type.value' => 'Filter by customer type value (exact match)',
        'type.value[]' => 'Filter by multiple customer type values (exact match)',
        'status.value' => 'Filter by customer status value (exact match)',
        'status.value[]' => 'Filter by multiple customer status values (exact match)',
        'value' => 'Filter by value (partial match)',
        'value[]' => 'Filter by value (partial match)',
        'confirmed' => 'Filter by customer confirmation status (true/false)',

        // Date filters
        'createdAt[before]' => 'Filter customers created before this date',
        'createdAt[strictly_before]' => 'Filter customers created strictly before this date',
        'createdAt[after]' => 'Filter customers created after this date',
        'createdAt[strictly_after]' => 'Filter customers created strictly after this date',
        'updatedAt[before]' => 'Filter customers updated before this date',
        'updatedAt[strictly_before]' => 'Filter customers updated strictly before this date',
        'updatedAt[after]' => 'Filter customers updated after this date',
        'updatedAt[strictly_after]' => 'Filter customers updated strictly after this date',

        // ULID range filters
        'ulid[between]' => 'Filter by ULID range (comma-separated start and end)',
        'ulid[gt]' => 'Filter by ULID greater than',
        'ulid[gte]' => 'Filter by ULID greater than or equal to',
        'ulid[lt]' => 'Filter by ULID less than',
        'ulid[lte]' => 'Filter by ULID less than or equal to',

        // Pagination
        'page' => 'Page number for pagination',
        'itemsPerPage' => 'Number of items per page',
    ];

    /**
     * @return array<string, string>
     */
    public static function descriptions(): array
    {
        return self::DESCRIPTIONS;
    }
}
