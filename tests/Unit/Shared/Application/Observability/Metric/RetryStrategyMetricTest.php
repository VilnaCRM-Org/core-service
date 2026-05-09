<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Observability\Metric;

use App\Shared\Application\Observability\Metric\DlqRoutingMetric;
use App\Shared\Application\Observability\Metric\RetryAttemptMetric;
use App\Shared\Application\Observability\Metric\ValueObject\MetricUnit;
use App\Tests\Unit\UnitTestCase;

final class RetryStrategyMetricTest extends UnitTestCase
{
    public function testRetryAttemptMetricUsesRetryDimensions(): void
    {
        $metric = RetryAttemptMetric::create(
            'App\Message\DomainEventEnvelope',
            'RuntimeException'
        );

        self::assertSame('MessengerRetryAttempts', $metric->name());
        self::assertSame(1, $metric->value());
        self::assertSame(MetricUnit::COUNT, $metric->unit()->value());
        self::assertSame('Messenger', $metric->dimensions()->values()->get('Endpoint'));
        self::assertSame('retry', $metric->dimensions()->values()->get('Operation'));
        self::assertSame(
            'DomainEventEnvelope',
            $metric->dimensions()->values()->get('MessageType')
        );
        self::assertSame(
            'RuntimeException',
            $metric->dimensions()->values()->get('ExceptionType')
        );
    }

    public function testDlqRoutingMetricUsesDlqDimensions(): void
    {
        $metric = DlqRoutingMetric::create(
            'App\Message\DomainEventEnvelope',
            'TypeError',
            2
        );

        self::assertSame('MessengerDlqRoutings', $metric->name());
        self::assertSame(2, $metric->value());
        self::assertSame(MetricUnit::COUNT, $metric->unit()->value());
        self::assertSame('Messenger', $metric->dimensions()->values()->get('Endpoint'));
        self::assertSame('dlq', $metric->dimensions()->values()->get('Operation'));
        self::assertSame(
            'DomainEventEnvelope',
            $metric->dimensions()->values()->get('MessageType')
        );
        self::assertSame('TypeError', $metric->dimensions()->values()->get('ExceptionType'));
    }
}
