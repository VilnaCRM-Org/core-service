<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability;

/**
 * AWS EMF Log Formatter
 *
 * Formats arrays as JSON for AWS CloudWatch Embedded Metric Format.
 */
final class EmfLogFormatter
{
    /**
     * @param array<string, int|float|string|array<string, int|float|string|array<int|string, int|float|string|array<int|string, int|float|string|array<string, string>>>>> $context
     */
    public function format(array $context): string
    {
        if ($context === []) {
            return '';
        }

        try {
            return json_encode($context, JSON_THROW_ON_ERROR) . "\n";
        } catch (\JsonException) {
            return '';
        }
    }
}
