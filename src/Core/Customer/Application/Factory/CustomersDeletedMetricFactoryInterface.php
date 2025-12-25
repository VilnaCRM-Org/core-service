<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Factory;

use App\Core\Customer\Application\Metric\CustomersDeletedMetric;

interface CustomersDeletedMetricFactoryInterface
{
    public function create(float|int $value = 1): CustomersDeletedMetric;
}
