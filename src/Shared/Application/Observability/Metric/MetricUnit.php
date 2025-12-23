<?php

declare(strict_types=1);

namespace App\Shared\Application\Observability\Metric;

/**
 * CloudWatch metric units as per AWS EMF specification
 */
enum MetricUnit: string
{
    case COUNT = 'Count';
    case NONE = 'None';
    case SECONDS = 'Seconds';
    case MILLISECONDS = 'Milliseconds';
    case BYTES = 'Bytes';
    case PERCENT = 'Percent';
}
