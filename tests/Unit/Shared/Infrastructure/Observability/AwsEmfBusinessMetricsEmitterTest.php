<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Observability;

use App\Shared\Infrastructure\Observability\AwsEmfBusinessMetricsEmitter;
use App\Tests\Unit\UnitTestCase;
use Psr\Log\LoggerInterface;

final class AwsEmfBusinessMetricsEmitterTest extends UnitTestCase
{
    private const NAMESPACE = 'CCore/BusinessMetrics';

    public function testEmitsValidEmfJsonForSingleMetric(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'emf_');
        self::assertIsString($file);

        $before = (int) (microtime(true) * 1000);

        $emitter = new AwsEmfBusinessMetricsEmitter(self::NAMESPACE, $file);
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

        $emitter = new AwsEmfBusinessMetricsEmitter(self::NAMESPACE, $file);
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

    public function testUsesCustomNamespace(): void
    {
        $customNamespace = 'CustomApp/Metrics';
        $file = tempnam(sys_get_temp_dir(), 'emf_');
        self::assertIsString($file);

        $emitter = new AwsEmfBusinessMetricsEmitter($customNamespace, $file);
        $emitter->emit('TestMetric', 1, ['Endpoint' => 'Test', 'Operation' => 'test']);

        $contents = file_get_contents($file);
        unlink($file);

        self::assertIsString($contents);
        $payload = json_decode(rtrim($contents, "\n"), true, flags: JSON_THROW_ON_ERROR);

        self::assertSame($customNamespace, $payload['_aws']['CloudWatchMetrics'][0]['Namespace']);
    }

    public function testDoesNotThrowWhenJsonEncodingFails(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'emf_');
        self::assertIsString($file);

        $emitter = new AwsEmfBusinessMetricsEmitter(self::NAMESPACE, $file);

        // Invalid UTF-8 -> json_encode(JSON_THROW_ON_ERROR) throws, but emitter must swallow it.
        $emitter->emit("\xB1", 1, ['Endpoint' => "\xB1", 'Operation' => 'get']);

        $contents = file_get_contents($file);
        unlink($file);

        self::assertIsString($contents);
        self::assertSame('', $contents);
    }

    public function testThrowsRuntimeExceptionWhenFileWriteFails(): void
    {
        // Use a directory path (not a file) to force file_put_contents to fail
        $invalidPath = sys_get_temp_dir() . '/non_existent_dir_' . uniqid() . '/file.log';

        $emitter = new AwsEmfBusinessMetricsEmitter(self::NAMESPACE, $invalidPath);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to open stream');

        $emitter->emit('TestMetric', 1, ['Endpoint' => 'Test', 'Operation' => 'test']);
    }

    public function testLogsErrorWithExceptionDetailsWhenJsonEncodingFails(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'emf_');
        self::assertIsString($file);

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('error')
            ->with(
                'Failed to encode EMF log to JSON',
                self::callback(static function (array $context): bool {
                    return isset($context['exception'])
                        && is_string($context['exception'])
                        && str_contains($context['exception'], 'Malformed UTF-8');
                })
            );

        $emitter = new AwsEmfBusinessMetricsEmitter(self::NAMESPACE, $file, $logger);

        // Invalid UTF-8 triggers JsonException
        $emitter->emit("\xB1", 1, ['Endpoint' => "\xB1", 'Operation' => 'get']);

        unlink($file);
    }

    public function testRestoresErrorHandlerAfterSuccessfulWrite(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'emf_');
        self::assertIsString($file);

        // Set a custom handler BEFORE emit
        $testHandlerCalled = false;
        set_error_handler(static function () use (&$testHandlerCalled): bool {
            $testHandlerCalled = true;

            return true;
        });

        try {
            $emitter = new AwsEmfBusinessMetricsEmitter(self::NAMESPACE, $file);
            $emitter->emit('TestMetric', 1, ['Endpoint' => 'Test', 'Operation' => 'test']);
            unlink($file);

            // After emit, trigger a warning - should call our handler, not throw
            trigger_error('Test warning', E_USER_WARNING);
        } finally {
            restore_error_handler();
        }

        self::assertTrue($testHandlerCalled, 'Original error handler was not restored');
    }

    public function testRestoresErrorHandlerEvenWhenWriteFails(): void
    {
        $invalidPath = sys_get_temp_dir() . '/non_existent_dir_' . uniqid() . '/file.log';

        // Set a custom handler BEFORE emit
        $testHandlerCalled = false;
        set_error_handler(static function () use (&$testHandlerCalled): bool {
            $testHandlerCalled = true;

            return true;
        });

        try {
            $emitter = new AwsEmfBusinessMetricsEmitter(self::NAMESPACE, $invalidPath);
            try {
                $emitter->emit('TestMetric', 1, ['Endpoint' => 'Test', 'Operation' => 'test']);
                self::fail('Expected RuntimeException was not thrown');
            } catch (\RuntimeException) {
                // Expected - the emit failed
            }

            // After emit fails, trigger a warning - should call our handler, not throw
            trigger_error('Test warning', E_USER_WARNING);
        } finally {
            restore_error_handler();
        }

        self::assertTrue($testHandlerCalled, 'Original error handler was not restored');
    }
}
