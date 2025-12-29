<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Metric;

use App\Shared\Application\Observability\Metric\EndpointOperationBusinessMetric;
use App\Shared\Application\Observability\Metric\ValueObject\MetricUnit;

/**
 * Metric for tracking customer creation events.
 *
 * Uses pure Value Objects without service dependencies (DDD compliant).
 */
final readonly class CustomersCreatedMetric extends EndpointOperationBusinessMetric
{
    private const ENDPOINT = 'Customer';
    private const OPERATION = 'create';

    public function __construct(float|int $value = 1)
    {
        parent::__construct($value, new MetricUnit(MetricUnit::COUNT));
    }

    public function name(): string
    {
        return 'CustomersCreated';
    }

    protected function endpoint(): string
    {
        return self::ENDPOINT;
    }

    protected function operation(): string
    {
        return self::OPERATION;
    }
}
