<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Shared\Application\Observability\Metric\ValueObject\MetricDimension;
use App\Tests\Unit\Shared\Infrastructure\Observability\BusinessMetricsEmitterSpy;

final class ObservabilityBusinessMetricsTest extends BaseTest
{
    public function testDoesNotEmitBusinessMetricForHealthEndpoint(): void
    {
        $client = self::createClient();
        $client->disableReboot();

        $spy = $client->getContainer()->get(BusinessMetricsEmitterSpy::class);
        $spy->clear();

        $client->request('GET', '/api/health');

        $this->assertResponseStatusCodeSame(204);

        // Re-get spy after request
        $spy = $client->getContainer()->get(BusinessMetricsEmitterSpy::class);

        // Health endpoints are excluded from business metrics
        // (high frequency, infrastructure concern)
        self::assertSame(0, $spy->count());
    }

    public function testEmitsBusinessMetricForCustomerEndpoint(): void
    {
        $client = self::createClient();
        $client->disableReboot();

        $spy = $client->getContainer()->get(BusinessMetricsEmitterSpy::class);
        $spy->clear();

        $client->request('GET', '/api/customers');

        $this->assertResponseIsSuccessful();

        // Re-get the spy from the kernel container after the request
        $spy = $client->getContainer()->get(BusinessMetricsEmitterSpy::class);

        // Verify at least one metric was emitted for the API call
        self::assertGreaterThan(0, $spy->count(), 'Expected at least one business metric to be emitted');

        // Verify the correct metric was emitted with correct dimensions
        $spy->assertEmittedWithDimensions(
            'EndpointInvocations',
            new MetricDimension('Endpoint', 'Customer'),
            new MetricDimension('Operation', '_api_/customers{._format}_get_collection')
        );
    }
}
