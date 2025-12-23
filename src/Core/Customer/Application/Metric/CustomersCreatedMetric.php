<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Metric;

use App\Shared\Application\Observability\Metric\BusinessMetric;
use App\Shared\Application\Observability\Metric\MetricUnit;

/**
 * Metric for tracking customer creation events
 */
final readonly class CustomersCreatedMetric extends BusinessMetric
{
    private const string ENDPOINT = 'Customer';
    private const string OPERATION = 'create';

    public function __construct(float|int $value = 1)
    {
        parent::__construct($value, MetricUnit::COUNT);
    }

    public function name(): string
    {
        return 'CustomersCreated';
    }

    /**
     * @return array<string, string>
     */
    public function dimensions(): array
    {
        return [
            'Endpoint' => self::ENDPOINT,
            'Operation' => self::OPERATION,
        ];
    }
}
