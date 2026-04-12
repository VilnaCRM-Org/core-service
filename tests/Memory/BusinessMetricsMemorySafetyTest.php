<?php

declare(strict_types=1);

namespace App\Tests\Memory;

use App\Tests\Integration\BaseApiCase;
use App\Tests\Support\Memory\ObjectDeallocationWatcher;
use App\Tests\Unit\Shared\Infrastructure\Observability\BusinessMetricsEmitterSpy;

final class BusinessMetricsMemorySafetyTest extends BaseApiCase
{
    public function testCustomerMetricsAreResetAndReleasedBetweenSameKernelRequests(): void
    {
        $client = $this->createSameKernelClient();
        $watcher = new ObjectDeallocationWatcher();

        $client->request('GET', '/api/customers');
        self::assertResponseIsSuccessful();

        $spy = $client->getContainer()->get(BusinessMetricsEmitterSpy::class);
        self::assertInstanceOf(BusinessMetricsEmitterSpy::class, $spy);

        $firstMetrics = $spy->emittedRaw();
        self::assertNotEmpty($firstMetrics);

        foreach ($firstMetrics as $index => $metric) {
            $watcher->expect(
                $metric,
                'GET /api/customers metric #' . ($index + 1)
            );
        }
        unset($metric);

        $firstMetricCount = count($firstMetrics);
        unset($firstMetrics);

        $client->request('GET', '/api/customers');
        self::assertResponseIsSuccessful();

        $spy = $client->getContainer()->get(BusinessMetricsEmitterSpy::class);
        self::assertInstanceOf(BusinessMetricsEmitterSpy::class, $spy);

        $secondMetrics = $spy->emittedRaw();
        self::assertCount($firstMetricCount, $secondMetrics);

        $spy->clear();

        unset($secondMetrics, $spy);

        $watcher->assertAllReleased($this);
    }
}
