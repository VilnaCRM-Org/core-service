<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Metric;

use App\Shared\Application\Observability\Metric\EndpointOperationBusinessMetric;
use App\Shared\Application\Observability\Metric\MetricUnit;
use App\Shared\Infrastructure\Observability\Factory\MetricDimensionsFactoryInterface;

/**
 * Metric for tracking customer update events
 */
final readonly class CustomersUpdatedMetric extends EndpointOperationBusinessMetric
{
    private const ENDPOINT = 'Customer';
    private const OPERATION = 'update';

    public function __construct(
        MetricDimensionsFactoryInterface $dimensionsFactory,
        float|int $value = 1
    ) {
        parent::__construct($dimensionsFactory, $value, MetricUnit::COUNT);
    }

    public function name(): string
    {
        return 'CustomersUpdated';
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
