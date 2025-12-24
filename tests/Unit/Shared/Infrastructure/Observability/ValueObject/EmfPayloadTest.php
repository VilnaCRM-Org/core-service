<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Observability\ValueObject;

use App\Shared\Infrastructure\Observability\Collection\EmfDimensionKeys;
use App\Shared\Infrastructure\Observability\Collection\EmfDimensionValueCollection;
use App\Shared\Infrastructure\Observability\Collection\EmfMetricDefinitionCollection;
use App\Shared\Infrastructure\Observability\Collection\EmfMetricValueCollection;
use App\Shared\Infrastructure\Observability\Exception\EmfKeyCollisionException;
use App\Shared\Infrastructure\Observability\ValueObject\EmfAwsMetadata;
use App\Shared\Infrastructure\Observability\ValueObject\EmfCloudWatchMetricConfig;
use App\Shared\Infrastructure\Observability\ValueObject\EmfDimensionValue;
use App\Shared\Infrastructure\Observability\ValueObject\EmfMetricDefinition;
use App\Shared\Infrastructure\Observability\ValueObject\EmfMetricValue;
use App\Shared\Infrastructure\Observability\ValueObject\EmfPayload;
use App\Tests\Unit\UnitTestCase;

final class EmfPayloadTest extends UnitTestCase
{
    public function testSerializesToCompleteEmfStructure(): void
    {
        $payload = $this->createPayload();

        $json = $payload->jsonSerialize();

        self::assertArrayHasKey('_aws', $json);
        self::assertArrayHasKey('Endpoint', $json);
        self::assertArrayHasKey('Operation', $json);
        self::assertArrayHasKey('CustomersCreated', $json);
    }

    public function testContainsCorrectAwsMetadata(): void
    {
        $payload = $this->createPayload();

        $json = $payload->jsonSerialize();

        self::assertSame(1702425600000, $json['_aws']['Timestamp']);
        self::assertSame('TestApp/Metrics', $json['_aws']['CloudWatchMetrics'][0]['Namespace']);
    }

    public function testContainsCorrectDimensionValues(): void
    {
        $payload = $this->createPayload();

        $json = $payload->jsonSerialize();

        self::assertSame('Customer', $json['Endpoint']);
        self::assertSame('create', $json['Operation']);
    }

    public function testContainsCorrectMetricValue(): void
    {
        $payload = $this->createPayload();

        $json = $payload->jsonSerialize();

        self::assertSame(1, $json['CustomersCreated']);
    }

    public function testWithAddedMetricCreatesNewPayload(): void
    {
        $payload = $this->createPayload();

        $newDefinition = new EmfMetricDefinition('OrdersPlaced', 'Count');
        $newValue = new EmfMetricValue('OrdersPlaced', 5);

        $updatedPayload = $payload->withAddedMetric($newDefinition, $newValue);

        self::assertNotSame($payload, $updatedPayload);

        $json = $updatedPayload->jsonSerialize();
        self::assertSame(1, $json['CustomersCreated']);
        self::assertSame(5, $json['OrdersPlaced']);
        self::assertCount(2, $json['_aws']['CloudWatchMetrics'][0]['Metrics']);
    }

    public function testAwsMetadataReturnsMetadata(): void
    {
        $payload = $this->createPayload();

        $metadata = $payload->awsMetadata();

        self::assertSame(1702425600000, $metadata->timestamp());
    }

    public function testDimensionValuesReturnsDimensions(): void
    {
        $payload = $this->createPayload();

        $dimensions = $payload->dimensionValues();

        self::assertCount(2, $dimensions);
    }

    public function testMetricValuesReturnsMetrics(): void
    {
        $payload = $this->createPayload();

        $metrics = $payload->metricValues();

        self::assertCount(1, $metrics);
    }

    public function testThrowsExceptionWhenDimensionKeyMatchesMetricName(): void
    {
        $this->expectException(EmfKeyCollisionException::class);
        $this->expectExceptionMessage('Key collision detected');

        $metricDefinition = new EmfMetricDefinition('Endpoint', 'Count');
        $dimensionKeys = new EmfDimensionKeys('Endpoint');
        $cloudWatchConfig = new EmfCloudWatchMetricConfig(
            'TestApp/Metrics',
            $dimensionKeys,
            new EmfMetricDefinitionCollection($metricDefinition)
        );
        $awsMetadata = new EmfAwsMetadata(1702425600000, $cloudWatchConfig);

        $dimensionValues = new EmfDimensionValueCollection(
            new EmfDimensionValue('Endpoint', 'Customer')
        );

        $metricValues = new EmfMetricValueCollection(
            new EmfMetricValue('Endpoint', 1)
        );

        new EmfPayload($awsMetadata, $dimensionValues, $metricValues);
    }

    public function testThrowsExceptionWhenReservedAwsKeyIsUsedAsDimension(): void
    {
        $this->expectException(EmfKeyCollisionException::class);
        $this->expectExceptionMessage('reserved for metadata');

        $metricDefinition = new EmfMetricDefinition('CustomersCreated', 'Count');
        $dimensionKeys = new EmfDimensionKeys('_aws');
        $cloudWatchConfig = new EmfCloudWatchMetricConfig(
            'TestApp/Metrics',
            $dimensionKeys,
            new EmfMetricDefinitionCollection($metricDefinition)
        );
        $awsMetadata = new EmfAwsMetadata(1702425600000, $cloudWatchConfig);

        $dimensionValues = new EmfDimensionValueCollection(
            new EmfDimensionValue('_aws', 'invalid')
        );

        $metricValues = new EmfMetricValueCollection(
            new EmfMetricValue('CustomersCreated', 1)
        );

        new EmfPayload($awsMetadata, $dimensionValues, $metricValues);
    }

    public function testThrowsExceptionWhenReservedAwsKeyIsUsedAsMetric(): void
    {
        $this->expectException(EmfKeyCollisionException::class);
        $this->expectExceptionMessage('reserved for metadata');

        $metricDefinition = new EmfMetricDefinition('_aws', 'Count');
        $dimensionKeys = new EmfDimensionKeys('Endpoint');
        $cloudWatchConfig = new EmfCloudWatchMetricConfig(
            'TestApp/Metrics',
            $dimensionKeys,
            new EmfMetricDefinitionCollection($metricDefinition)
        );
        $awsMetadata = new EmfAwsMetadata(1702425600000, $cloudWatchConfig);

        $dimensionValues = new EmfDimensionValueCollection(
            new EmfDimensionValue('Endpoint', 'Customer')
        );

        $metricValues = new EmfMetricValueCollection(
            new EmfMetricValue('_aws', 1)
        );

        new EmfPayload($awsMetadata, $dimensionValues, $metricValues);
    }

    private function createPayload(): EmfPayload
    {
        $metricDefinition = new EmfMetricDefinition('CustomersCreated', 'Count');
        $dimensionKeys = new EmfDimensionKeys('Endpoint', 'Operation');
        $cloudWatchConfig = new EmfCloudWatchMetricConfig(
            'TestApp/Metrics',
            $dimensionKeys,
            new EmfMetricDefinitionCollection($metricDefinition)
        );
        $awsMetadata = new EmfAwsMetadata(1702425600000, $cloudWatchConfig);

        $dimensionValues = new EmfDimensionValueCollection(
            new EmfDimensionValue('Endpoint', 'Customer'),
            new EmfDimensionValue('Operation', 'create')
        );

        $metricValues = new EmfMetricValueCollection(
            new EmfMetricValue('CustomersCreated', 1)
        );

        return new EmfPayload($awsMetadata, $dimensionValues, $metricValues);
    }
}
