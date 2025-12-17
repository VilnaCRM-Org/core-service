<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\Unit\Shared\Infrastructure\Observability\BusinessMetricsEmitterSpy;

final class ObservabilityBusinessMetricsTest extends BaseTest
{
    public function testEmitsBusinessMetricForHealthEndpoint(): void
    {
        $client = self::createClient();
        $client->disableReboot();

        $spy = self::getContainer()->get(BusinessMetricsEmitterSpy::class);
        $spy->clear();

        $client->request('GET', '/api/health');

        $this->assertResponseStatusCodeSame(204);

        $spy->assertEmittedWithDimensions('EndpointInvocations', [
            'Endpoint' => 'HealthCheck',
            'Operation' => '_api_/health_get',
        ]);
    }
}
