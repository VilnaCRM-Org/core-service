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

        $spy = self::getContainer()->get(BusinessMetricsEmitterSpy::class);
        $spy->clear();

        $client->request('GET', '/api/health');

        $this->assertResponseStatusCodeSame(204);

        // Health endpoints are excluded from business metrics
        // (high frequency, infrastructure concern)
        self::assertSame(0, $spy->count());
    }

    public function testEmitsBusinessMetricForCustomerEndpoint(): void
    {
        $client = self::createClient();
        $client->disableReboot();

        $spy = self::getContainer()->get(BusinessMetricsEmitterSpy::class);
        $spy->clear();

        $client->request('GET', '/api/customers');

        $this->assertResponseIsSuccessful();

        $spy->assertEmittedWithDimensions(
            'EndpointInvocations',
            new MetricDimension('Endpoint', 'Customer'),
            new MetricDimension('Operation', '_api_/customers_get_collection')
        );
    }
}
