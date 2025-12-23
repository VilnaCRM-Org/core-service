<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Observability;

use App\Shared\Application\Observability\Metric\EndpointInvocationsMetric;
use App\Shared\Application\Observability\Metric\MetricCollection;
use App\Shared\Infrastructure\Observability\AwsEmfBusinessMetricsEmitter;
use App\Shared\Infrastructure\Observability\Emf\EmfPayloadFactory;
use App\Shared\Infrastructure\Observability\Emf\SystemEmfTimestampProvider;
use App\Shared\Infrastructure\Observability\EmfLogFormatter;
use App\Tests\Unit\Shared\Application\Observability\Metric\TestCustomerMetric;
use App\Tests\Unit\Shared\Application\Observability\Metric\TestOrdersPlacedMetric;
use App\Tests\Unit\Shared\Application\Observability\Metric\TestOrderValueMetric;
use App\Tests\Unit\UnitTestCase;
use Psr\Log\LoggerInterface;

final class AwsEmfBusinessMetricsEmitterTest extends UnitTestCase
{
    private const string NAMESPACE = 'CCore/BusinessMetrics';

    /** @var array<string, mixed>|null */
    private ?array $capturedContext = null;

    public function testEmitsValidEmfPayloadForSingleMetric(): void
    {
        $before = (int) (microtime(true) * 1000);
        $emitter = $this->createEmitterWithContextCapture();

        $emitter->emit(new EndpointInvocationsMetric(new \App\Shared\Infrastructure\Observability\Factory\MetricDimensionsFactory(), 'HealthCheck', 'get'));

        $this->assertTimestampWithinRange($before);
        $this->assertSingleMetricValues();
        $this->assertSingleMetricEmfStructure();
    }

    public function testEmitsValidEmfPayloadForMetricCollection(): void
    {
        $before = (int) (microtime(true) * 1000);
        $emitter = $this->createEmitterWithContextCapture();

        $emitter->emitCollection($this->createOrderMetricCollection());

        $this->assertTimestampWithinRange($before);
        $this->assertCollectionMetricValues();
        $this->assertCollectionEmfStructure();
    }

    public function testUsesCustomNamespace(): void
    {
        $customNamespace = 'CustomApp/Metrics';
        $emitter = $this->createEmitterWithContextCapture($customNamespace);

        $emitter->emit(new EndpointInvocationsMetric(new \App\Shared\Infrastructure\Observability\Factory\MetricDimensionsFactory(), 'Test', 'test'));

        $namespace = $this->capturedContext['_aws']['CloudWatchMetrics'][0]['Namespace'];
        self::assertSame($customNamespace, $namespace);
    }

    public function testDoesNotEmitForEmptyCollection(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('info');

        $emitter = $this->createEmitterWithLogger($logger);
        $emitter->emitCollection(new MetricCollection());
    }

    public function testDoesNotEmitWhenEmfPayloadCannotBeEncoded(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('info');

        $emitter = $this->createEmitterWithLogger($logger);

        $metric = new class() extends \App\Shared\Application\Observability\Metric\BusinessMetric {
            public function __construct()
            {
                parent::__construct(1, \App\Shared\Application\Observability\Metric\MetricUnit::COUNT);
            }

            public function name(): string
            {
                return 'InvalidMetric';
            }

            public function dimensions(): \App\Shared\Application\Observability\Metric\MetricDimensionsInterface
            {
                return new class() implements \App\Shared\Application\Observability\Metric\MetricDimensionsInterface {
                    public function values(): \App\Shared\Application\Observability\Metric\MetricDimensions
                    {
                        return new \App\Shared\Application\Observability\Metric\MetricDimensions(
                            new \App\Shared\Application\Observability\Metric\MetricDimension('Endpoint', "\xB1"), // Invalid UTF-8
                            new \App\Shared\Application\Observability\Metric\MetricDimension('Operation', 'create')
                        );
                    }
                };
            }
        };

        $emitter->emit($metric);
    }

    public function testMetricValueIsCorrectlySet(): void
    {
        $emitter = $this->createEmitterWithContextCapture();

        $emitter->emit(new EndpointInvocationsMetric(new \App\Shared\Infrastructure\Observability\Factory\MetricDimensionsFactory(), 'Customer', 'create', 42));

        self::assertSame(42, $this->capturedContext['EndpointInvocations']);
    }

    public function testCollectionUsesDimensionsFromFirstMetric(): void
    {
        $emitter = $this->createEmitterWithContextCapture();

        $dimensionsFactory = new \App\Shared\Infrastructure\Observability\Factory\MetricDimensionsFactory();

        $collection = new MetricCollection(
            new TestOrdersPlacedMetric($dimensionsFactory, 1),
            new TestCustomerMetric($dimensionsFactory, 1)
        );
        $emitter->emitCollection($collection);

        self::assertSame('Order', $this->capturedContext['Endpoint']);
        self::assertSame('create', $this->capturedContext['Operation']);
        $dims = $this->capturedContext['_aws']['CloudWatchMetrics'][0]['Dimensions'];
        self::assertSame([['Endpoint', 'Operation']], $dims);
    }

    private function createEmitterWithContextCapture(
        string $namespace = self::NAMESPACE
    ): AwsEmfBusinessMetricsEmitter {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('info')
            ->with($this->callback(function (string $message): bool {
                $decoded = json_decode(rtrim($message, "\n"), true);
                self::assertIsArray($decoded);

                /** @var array<string, mixed> $decoded */
                $this->capturedContext = $decoded;

                return true;
            }));

        return $this->createEmitterWithLoggerAndNamespace($logger, $namespace);
    }

    private function createEmitterWithLogger(LoggerInterface $logger): AwsEmfBusinessMetricsEmitter
    {
        return $this->createEmitterWithLoggerAndNamespace($logger, self::NAMESPACE);
    }

    private function createEmitterWithLoggerAndNamespace(
        LoggerInterface $logger,
        string $namespace
    ): AwsEmfBusinessMetricsEmitter {
        $timestampProvider = new SystemEmfTimestampProvider();
        $payloadFactory = new EmfPayloadFactory($namespace, $timestampProvider);

        return new AwsEmfBusinessMetricsEmitter($logger, new EmfLogFormatter(), $payloadFactory);
    }

    private function createOrderMetricCollection(): MetricCollection
    {
        $dimensionsFactory = new \App\Shared\Infrastructure\Observability\Factory\MetricDimensionsFactory();

        return new MetricCollection(
            new TestOrdersPlacedMetric($dimensionsFactory, 1),
            new TestOrderValueMetric($dimensionsFactory, 99.9)
        );
    }

    private function assertTimestampWithinRange(int $before): void
    {
        self::assertArrayHasKey('_aws', $this->capturedContext);
        self::assertIsInt($this->capturedContext['_aws']['Timestamp']);
        self::assertGreaterThanOrEqual($before, $this->capturedContext['_aws']['Timestamp']);
        self::assertLessThanOrEqual($before + 10_000, $this->capturedContext['_aws']['Timestamp']);
    }

    private function assertSingleMetricValues(): void
    {
        self::assertSame(1, $this->capturedContext['EndpointInvocations']);
        self::assertSame('HealthCheck', $this->capturedContext['Endpoint']);
        self::assertSame('get', $this->capturedContext['Operation']);
    }

    private function assertSingleMetricEmfStructure(): void
    {
        $cw = $this->capturedContext['_aws']['CloudWatchMetrics'][0];
        self::assertSame(self::NAMESPACE, $cw['Namespace']);
        self::assertSame([['Endpoint', 'Operation']], $cw['Dimensions']);
        self::assertSame('EndpointInvocations', $cw['Metrics'][0]['Name']);
        self::assertSame('Count', $cw['Metrics'][0]['Unit']);
    }

    private function assertCollectionMetricValues(): void
    {
        self::assertSame(1, $this->capturedContext['OrdersPlaced']);
        self::assertSame(99.9, $this->capturedContext['OrderValue']);
        self::assertSame('Order', $this->capturedContext['Endpoint']);
        self::assertSame('create', $this->capturedContext['Operation']);
    }

    private function assertCollectionEmfStructure(): void
    {
        $cw = $this->capturedContext['_aws']['CloudWatchMetrics'][0];
        self::assertSame(self::NAMESPACE, $cw['Namespace']);
        self::assertSame([['Endpoint', 'Operation']], $cw['Dimensions']);
        $metrics = $cw['Metrics'];
        self::assertCount(2, $metrics);
        self::assertSame(['Name' => 'OrdersPlaced', 'Unit' => 'Count'], $metrics[0]);
        self::assertSame(['Name' => 'OrderValue', 'Unit' => 'None'], $metrics[1]);
    }
}
