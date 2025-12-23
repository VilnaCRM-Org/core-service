<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Observability;

use App\Shared\Infrastructure\Observability\Emf\EmfAwsMetadata;
use App\Shared\Infrastructure\Observability\Emf\EmfCloudWatchMetricConfig;
use App\Shared\Infrastructure\Observability\Emf\EmfDimensionKeys;
use App\Shared\Infrastructure\Observability\Emf\EmfDimensionValue;
use App\Shared\Infrastructure\Observability\Emf\EmfDimensionValueCollection;
use App\Shared\Infrastructure\Observability\Emf\EmfMetricDefinition;
use App\Shared\Infrastructure\Observability\Emf\EmfMetricDefinitionCollection;
use App\Shared\Infrastructure\Observability\Emf\EmfMetricValue;
use App\Shared\Infrastructure\Observability\Emf\EmfMetricValueCollection;
use App\Shared\Infrastructure\Observability\Emf\EmfPayload;
use App\Shared\Infrastructure\Observability\EmfLogFormatter;
use App\Tests\Unit\UnitTestCase;

final class EmfLogFormatterTest extends UnitTestCase
{
    private EmfLogFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formatter = new EmfLogFormatter();
    }

    public function testFormatsPayloadAsJson(): void
    {
        $payload = $this->createTestPayload();

        $formatted = $this->formatter->format($payload);

        self::assertStringStartsWith('{', $formatted);
        self::assertStringEndsWith("}\n", $formatted);

        $decoded = json_decode(rtrim($formatted, "\n"), true);
        $expected = [
            '_aws' => [
                'Timestamp' => 1702425600000,
                'CloudWatchMetrics' => [
                    [
                        'Namespace' => 'CCore/BusinessMetrics',
                        'Dimensions' => [['Endpoint', 'Operation']],
                        'Metrics' => [['Name' => 'CustomersCreated', 'Unit' => 'Count']],
                    ],
                ],
            ],
            'Endpoint' => 'Customer',
            'Operation' => 'create',
            'CustomersCreated' => 1,
        ];
        self::assertSame($expected, $decoded);
    }

    public function testFormatsPayloadWithProperStructure(): void
    {
        $payload = $this->createTestPayload();

        $formatted = $this->formatter->format($payload);
        $decoded = json_decode(rtrim($formatted, "\n"), true);

        self::assertArrayHasKey('_aws', $decoded);
        self::assertArrayHasKey('Timestamp', $decoded['_aws']);
        self::assertArrayHasKey('CloudWatchMetrics', $decoded['_aws']);
        self::assertArrayHasKey('Endpoint', $decoded);
        self::assertArrayHasKey('Operation', $decoded);
        self::assertArrayHasKey('CustomersCreated', $decoded);
    }

    private function createTestPayload(): EmfPayload
    {
        $metricDefinition = new EmfMetricDefinition('CustomersCreated', 'Count');
        $dimensionKeys = new EmfDimensionKeys('Endpoint', 'Operation');
        $cloudWatchConfig = new EmfCloudWatchMetricConfig(
            'CCore/BusinessMetrics',
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
