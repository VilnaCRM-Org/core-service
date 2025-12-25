<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Observability\Factory;

use App\Shared\Application\Observability\Metric\Collection\MetricCollection;
use App\Shared\Application\Observability\Metric\EndpointInvocationsMetric;
use App\Shared\Infrastructure\Observability\Exception\InvalidEmfNamespaceException;
use App\Shared\Infrastructure\Observability\Factory\EmfPayloadFactory;
use App\Shared\Infrastructure\Observability\Factory\MetricDimensionsFactory;
use App\Shared\Infrastructure\Observability\Provider\EmfTimestampProvider;
use App\Shared\Infrastructure\Observability\Validator\EmfDimensionValueValidatorService;
use App\Shared\Infrastructure\Observability\Validator\EmfNamespaceValidatorService;
use App\Tests\Unit\Shared\Application\Observability\Metric\TestOrdersPlacedMetric;
use App\Tests\Unit\Shared\Application\Observability\Metric\TestOrderValueMetric;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\Validation;

final class EmfPayloadFactoryTest extends UnitTestCase
{
    public const int FIXED_TIMESTAMP = 1702425600000;
    private const string NAMESPACE = 'TestApp/Metrics';

    public function testCreatesPayloadFromSingleMetric(): void
    {
        $factory = $this->createFactory();
        $metric = new EndpointInvocationsMetric(new MetricDimensionsFactory(), 'Customer', 'create');

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
        $dimensionsFactory = new MetricDimensionsFactory();

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
        $factory = $this->createFactoryWithNamespace($customNamespace);
        $metric = new EndpointInvocationsMetric(new MetricDimensionsFactory(), 'Test', 'test');

        $payload = $factory->createFromMetric($metric);

        $json = json_decode(json_encode($payload), true);
        self::assertSame($customNamespace, $json['_aws']['CloudWatchMetrics'][0]['Namespace']);
    }

    public function testCollectionUsesDimensionsFromFirstMetric(): void
    {
        $factory = $this->createFactory();
        $dimensionsFactory = new MetricDimensionsFactory();

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

    public function testThrowsExceptionForInvalidNamespaceWithSpecialCharacters(): void
    {
        $this->expectException(InvalidEmfNamespaceException::class);
        $this->expectExceptionMessage('alphanumeric characters');

        $this->createFactoryWithNamespace('MyApp@Metrics');
    }

    public function testThrowsExceptionForEmptyNamespace(): void
    {
        $this->expectException(InvalidEmfNamespaceException::class);
        $this->expectExceptionMessage('non-whitespace character');

        $this->createFactoryWithNamespace('');
    }

    public function testThrowsExceptionForNamespaceTooLong(): void
    {
        $this->expectException(InvalidEmfNamespaceException::class);
        $this->expectExceptionMessage('must not exceed 256 characters');

        $this->createFactoryWithNamespace(str_repeat('a', 257));
    }

    public function testAcceptsValidNamespaceWithSlashes(): void
    {
        $factory = $this->createFactoryWithNamespace('MyApp/BusinessMetrics');

        self::assertInstanceOf(EmfPayloadFactory::class, $factory);
    }

    public function testAcceptsValidNamespaceWithAllAllowedCharacters(): void
    {
        $factory = $this->createFactoryWithNamespace('ABC-123.abc_xyz/test#v1:prod');

        self::assertInstanceOf(EmfPayloadFactory::class, $factory);
    }

    private function createFactory(): EmfPayloadFactory
    {
        return $this->createFactoryWithNamespace(self::NAMESPACE);
    }

    private function createFactoryWithNamespace(string $namespace): EmfPayloadFactory
    {
        $validator = Validation::createValidatorBuilder()
            ->addYamlMapping(__DIR__ . '/../../../../../../config/validator/EmfDimensionValue.yaml')
            ->addYamlMapping(__DIR__ . '/../../../../../../config/validator/EmfNamespaceValue.yaml')
            ->getValidator();

        return new EmfPayloadFactory(
            $namespace,
            $this->createTimestampProvider(),
            new EmfNamespaceValidatorService($validator),
            new EmfDimensionValueValidatorService($validator)
        );
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
