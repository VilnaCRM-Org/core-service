<?php

declare(strict_types=1);

namespace App\Shared\Application\Observability\Factory;

use App\Shared\Application\Command\CacheRefreshCommand;
use App\Shared\Application\Observability\Metric\CacheRefreshFailedMetric;
use App\Shared\Application\Observability\Metric\CacheRefreshScheduledMetric;
use App\Shared\Application\Observability\Metric\CacheRefreshSucceededMetric;

final readonly class CacheRefreshMetricFactory
{
    public function scheduled(CacheRefreshCommand $command): CacheRefreshScheduledMetric
    {
        return new CacheRefreshScheduledMetric(
            $command->context(),
            $command->family(),
            $command->refreshSource()
        );
    }

    public function succeeded(CacheRefreshCommand $command): CacheRefreshSucceededMetric
    {
        return new CacheRefreshSucceededMetric(
            $command->context(),
            $command->family(),
            $command->refreshSource()
        );
    }

    public function failed(CacheRefreshCommand $command): CacheRefreshFailedMetric
    {
        return new CacheRefreshFailedMetric(
            $command->context(),
            $command->family(),
            $command->refreshSource()
        );
    }
}
