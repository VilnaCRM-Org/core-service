<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi;

/**
 * Provides the shared dictionary of tag descriptions used across
 * OpenAPI processors and augmenters. Keeping the values in a single place avoids
 * duplicating the descriptions across multiple classes.
 */
final class TagDescriptionDictionary
{
    private const DESCRIPTIONS = [
        'Customer' => 'Operations related to customer management',
        'CustomerStatus' => 'Operations related to customer status management',
        'CustomerType' => 'Operations related to customer type management',
        'HealthCheck' => 'Health check endpoints for monitoring',
    ];

    /**
     * @return array<string, string>
     */
    public static function descriptions(): array
    {
        return self::DESCRIPTIONS;
    }
}
