<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Observability;

use App\Shared\Infrastructure\Observability\AwsEmfBusinessMetricsEmitter;
use App\Tests\Unit\UnitTestCase;

final class AwsEmfBusinessMetricsEmitterTest extends UnitTestCase
{
    public function testEmitsValidEmfJsonForSingleMetric(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'emf_');
        self::assertIsString($file);

        $before = (int) (microtime(true) * 1000);

        $emitter = new AwsEmfBusinessMetricsEmitter($file);
        $emitter->emit('EndpointInvocations', 1, [
            'Endpoint' => 'HealthCheck',
            'Operation' => 'get',
        ]);

        $contents = file_get_contents($file);
        unlink($file);

        self::assertIsString($contents);
        self::assertTrue(str_starts_with($contents, '{'));
        self::assertTrue(str_ends_with($contents, "\n"));

        $payload = json_decode(rtrim($contents, "\n"), true, flags: JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('_aws', $payload);
        self::assertIsInt($payload['_aws']['Timestamp']);
        self::assertGreaterThanOrEqual($before, $payload['_aws']['Timestamp']);
        self::assertLessThanOrEqual($before + 10_000, $payload['_aws']['Timestamp']);

        self::assertSame(1, $payload['EndpointInvocations']);
        self::assertSame('HealthCheck', $payload['Endpoint']);
        self::assertSame('get', $payload['Operation']);

        self::assertSame('CCore/BusinessMetrics', $payload['_aws']['CloudWatchMetrics'][0]['Namespace']);
        self::assertSame([['Endpoint', 'Operation']], $payload['_aws']['CloudWatchMetrics'][0]['Dimensions']);
        self::assertSame('EndpointInvocations', $payload['_aws']['CloudWatchMetrics'][0]['Metrics'][0]['Name']);
        self::assertSame('Count', $payload['_aws']['CloudWatchMetrics'][0]['Metrics'][0]['Unit']);
    }

    public function testEmitsValidEmfJsonForMultipleMetrics(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'emf_');
        self::assertIsString($file);

        $before = (int) (microtime(true) * 1000);

        $emitter = new AwsEmfBusinessMetricsEmitter($file);
        $emitter->emitMultiple([
            'OrdersPlaced' => ['value' => 1, 'unit' => 'Count'],
            'OrderValue' => ['value' => 99.9, 'unit' => 'None'],
        ], [
            'Endpoint' => 'Order',
            'Operation' => 'create',
        ]);

        $contents = file_get_contents($file);
        unlink($file);

        self::assertIsString($contents);
        self::assertTrue(str_starts_with($contents, '{'));
        self::assertTrue(str_ends_with($contents, "\n"));

        $payload = json_decode(rtrim($contents, "\n"), true, flags: JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('_aws', $payload);
        self::assertIsInt($payload['_aws']['Timestamp']);
        self::assertGreaterThanOrEqual($before, $payload['_aws']['Timestamp']);
        self::assertLessThanOrEqual($before + 10_000, $payload['_aws']['Timestamp']);

        self::assertSame(1, $payload['OrdersPlaced']);
        self::assertSame(99.9, $payload['OrderValue']);
        self::assertSame('Order', $payload['Endpoint']);
        self::assertSame('create', $payload['Operation']);

        $cw = $payload['_aws']['CloudWatchMetrics'][0];
        self::assertSame('CCore/BusinessMetrics', $cw['Namespace']);
        self::assertSame([['Endpoint', 'Operation']], $cw['Dimensions']);

        $metrics = $cw['Metrics'];
        self::assertCount(2, $metrics);
        self::assertSame(['Name' => 'OrdersPlaced', 'Unit' => 'Count'], $metrics[0]);
        self::assertSame(['Name' => 'OrderValue', 'Unit' => 'None'], $metrics[1]);
    }

    public function testDoesNotThrowWhenJsonEncodingFails(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'emf_');
        self::assertIsString($file);

        $emitter = new AwsEmfBusinessMetricsEmitter($file);

        // Invalid UTF-8 -> json_encode(JSON_THROW_ON_ERROR) throws, but emitter must swallow it.
        $emitter->emit("\xB1", 1, ['Endpoint' => "\xB1", 'Operation' => 'get']);

        $contents = file_get_contents($file);
        unlink($file);

        self::assertIsString($contents);
        self::assertSame('', $contents);
    }
}
