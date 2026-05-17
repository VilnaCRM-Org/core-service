<?php

declare(strict_types=1);

namespace App\Shared\Application\Observability\Factory;

use App\Shared\Application\Observability\Metric\DlqRoutingMetric;
use App\Shared\Application\Observability\Metric\RetryAttemptMetric;

final readonly class RetryStrategyMetricFactory
{
    public function retryAttempt(string $messageType, string $exceptionType): RetryAttemptMetric
    {
        return new RetryAttemptMetric($messageType, $exceptionType);
    }

    public function dlqRouting(string $messageType, string $exceptionType): DlqRoutingMetric
    {
        return new DlqRoutingMetric($messageType, $exceptionType);
    }
}
