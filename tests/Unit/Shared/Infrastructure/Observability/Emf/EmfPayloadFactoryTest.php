<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Observability\Emf;

use App\Shared\Application\Observability\Metric\EndpointInvocationsMetric;
use App\Shared\Application\Observability\Metric\MetricCollection;
use App\Shared\Infrastructure\Observability\Emf\EmfTimestampProvider;
use App\Shared\Infrastructure\Observability\Factory\EmfPayloadFactory;
use App\Tests\Unit\Shared\Application\Observability\Metric\TestOrdersPlacedMetric;
use App\Tests\Unit\Shared\Application\Observability\Metric\TestOrderValueMetric;
use App\Tests\Unit\UnitTestCase;

final class EmfPayloadFactoryTest extends UnitTestCase
{
    public const int FIXED_TIMESTAMP = 1702425600000;
    private const string NAMESPACE = 'TestApp/Metrics';

    public function testCreatesPayloadFromSingleMetric(): void
    {
        $factory = $this->createFactory();
        $metric = new EndpointInvocationsMetric(new \App\Shared\Infrastructure\Observability\Factory\MetricDimensionsFactory(), 'Customer', 'create');

        $payload = $factory->createFromMetric($metric);

        $json = json_decode(json_encode($payload), true);

        self::assertSame(self::FIXED_TIMESTAMP, $json['_aws']['Timestamp']);
        self::assertSame(self::NAMESPACE, $json['_aws']['CloudWatchMetrics'][0]['Namespace']);
        self::assertSame([['Endpoint', 'Operation']], $json['_aws']['CloudWatchMetrics'][0]['Dimensions']);
        self::assertSame('EndpointInvocations', $json['_aws']['CloudWatchMetrics'][0]['Metrics'][0]['Name']);
        self::assertSame('Count', $json['_aws']['CloudWatchMetrics'][0]['Metrics'][0]['Unit']);
        self::assertSame('Customer', $json['Endpoint']);
        self::assertSame('create', $json['Operation']);
        self::assertSame(1, $json['EndpointInvocations']);
    }

    public function testCreatesPayloadFromMetricCollection(): void
    {
        $factory = $this->createFactory();
        $dimensionsFactory = new \App\Shared\Infrastructure\Observability\Factory\MetricDimensionsFactory();

        $collection = new MetricCollection(
            new TestOrdersPlacedMetric($dimensionsFactory, 5),
            new TestOrderValueMetric($dimensionsFactory, 199.99)
        );

        $payload = $factory->createFromCollection($collection);

        $json = json_decode(json_encode($payload), true);

        self::assertSame(self::FIXED_TIMESTAMP, $json['_aws']['Timestamp']);
        self::assertSame(self::NAMESPACE, $json['_aws']['CloudWatchMetrics'][0]['Namespace']);
        self::assertCount(2, $json['_aws']['CloudWatchMetrics'][0]['Metrics']);
        self::assertSame('OrdersPlaced', $json['_aws']['CloudWatchMetrics'][0]['Metrics'][0]['Name']);
        self::assertSame('OrderValue', $json['_aws']['CloudWatchMetrics'][0]['Metrics'][1]['Name']);
        self::assertSame(5, $json['OrdersPlaced']);
        self::assertSame(199.99, $json['OrderValue']);
    }

    public function testUsesProvidedNamespace(): void
    {
        $customNamespace = 'CustomApp/BusinessMetrics';
        $factory = new EmfPayloadFactory($customNamespace, $this->createTimestampProvider());
        $metric = new EndpointInvocationsMetric(new \App\Shared\Infrastructure\Observability\Factory\MetricDimensionsFactory(), 'Test', 'test');

        $payload = $factory->createFromMetric($metric);

        $json = json_decode(json_encode($payload), true);
        self::assertSame($customNamespace, $json['_aws']['CloudWatchMetrics'][0]['Namespace']);
    }

    public function testCollectionUsesDimensionsFromFirstMetric(): void
    {
        $factory = $this->createFactory();
        $dimensionsFactory = new \App\Shared\Infrastructure\Observability\Factory\MetricDimensionsFactory();

        $collection = new MetricCollection(
            new TestOrdersPlacedMetric($dimensionsFactory, 1),
            new TestOrderValueMetric($dimensionsFactory, 50.0)
        );

        $payload = $factory->createFromCollection($collection);

        $json = json_decode(json_encode($payload), true);
        self::assertSame('Order', $json['Endpoint']);
        self::assertSame('create', $json['Operation']);
    }

    public function testThrowsExceptionForEmptyCollection(): void
    {
        $factory = $this->createFactory();
        $emptyCollection = new MetricCollection();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot create EMF payload from empty metric collection');

        $factory->createFromCollection($emptyCollection);
    }

    private function createFactory(): EmfPayloadFactory
    {
        return new EmfPayloadFactory(self::NAMESPACE, $this->createTimestampProvider());
    }

    private function createTimestampProvider(): EmfTimestampProvider
    {
        return new class() implements EmfTimestampProvider {
            public function currentTimestamp(): int
            {
                return EmfPayloadFactoryTest::FIXED_TIMESTAMP;
            }
        };
    }
}
