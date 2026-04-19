<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Observability;

use App\Shared\Application\Observability\Metric\ValueObject\MetricUnit;
use App\Tests\Unit\Shared\Application\Observability\Metric\TestBusinessMetric;
use App\Tests\Unit\UnitTestCase;

final class BusinessMetricsEmitterSpyTest extends UnitTestCase
{
    public function testClearPreservesPendingFailureForTheNextEmit(): void
    {
        $spy = new BusinessMetricsEmitterSpy();
        $spy->emit(new TestBusinessMetric(1, new MetricUnit(MetricUnit::COUNT)));
        $spy->failOnNextCall();

        $spy->clear();

        self::assertSame(0, $spy->count());
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Metric emission failed');
        $spy->emit(new TestBusinessMetric(2, new MetricUnit(MetricUnit::COUNT)));
    }

    public function testResetClearsMetricsAndFailureState(): void
    {
        $spy = new BusinessMetricsEmitterSpy();
        $spy->emit(new TestBusinessMetric(1, new MetricUnit(MetricUnit::COUNT)));
        $spy->failOnNextCall();

        $spy->reset();

        self::assertSame(0, $spy->count());
        $spy->emit(new TestBusinessMetric(2, new MetricUnit(MetricUnit::COUNT)));
        self::assertSame(1, $spy->count());
    }
}
