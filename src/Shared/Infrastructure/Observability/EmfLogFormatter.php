<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability;

use App\Shared\Infrastructure\Observability\Emf\EmfPayload;

/**
 * AWS EMF Log Formatter
 *
 * Formats EmfPayload objects as JSON for AWS CloudWatch Embedded Metric Format.
 */
final class EmfLogFormatter
{
    public function format(EmfPayload $payload): string
    {
        try {
            return json_encode($payload, JSON_THROW_ON_ERROR) . "\n";
        } catch (\JsonException) {
            return '';
        }
    }
}
