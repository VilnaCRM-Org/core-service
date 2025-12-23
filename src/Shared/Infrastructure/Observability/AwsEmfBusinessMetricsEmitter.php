<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability;

use App\Shared\Application\Observability\BusinessMetricsEmitterInterface;
use App\Shared\Application\Observability\Metric\BusinessMetric;
use App\Shared\Application\Observability\Metric\MetricCollection;
use Psr\Log\LoggerInterface;

/**
 * AWS EMF (Embedded Metric Format) Business Metrics Emitter
 *
 * Emits business metrics in AWS EMF format via Symfony logger.
 * CloudWatch automatically extracts metrics from EMF-formatted logs.
 */
final readonly class AwsEmfBusinessMetricsEmitter implements BusinessMetricsEmitterInterface
{
    private const string DEFAULT_NAMESPACE = 'CCore/BusinessMetrics';

    public function __construct(
        private LoggerInterface $logger,
        private string $namespace = self::DEFAULT_NAMESPACE
    ) {
    }

    public function emit(BusinessMetric $metric): void
    {
        $emfLog = $this->buildEmfPayload($metric);

        $this->writeEmfLog($emfLog);
    }

    public function emitCollection(MetricCollection $metrics): void
    {
        if ($metrics->isEmpty()) {
            return;
        }

        $allMetrics = $metrics->all();
        $firstMetric = $allMetrics[0];

        $emfLog = $this->buildEmfPayloadForCollection($allMetrics, $firstMetric->dimensions());

        $this->writeEmfLog($emfLog);
    }

    /**
     * @return array<string, int|float|string|array<string, int|float|string|array<int|string, int|float|string|array<int|string, int|float|string|array<string, string>>>>>
     */
    private function buildEmfPayload(BusinessMetric $metric): array
    {
        $dimensions = $metric->dimensions();

        $emfLog = $this->createBaseEmfLog($dimensions, $metric->name(), $metric->unit()->value);

        return array_merge($emfLog, $dimensions, [$metric->name() => $metric->value()]);
    }

    /**
     * @param array<int, BusinessMetric> $metrics
     * @param array<string, string> $dimensions
     *
     * @return array<string, int|float|string|array<string, int|float|string|array<int|string, int|float|string|array<int|string, int|float|string|array<string, string>>>>>
     */
    private function buildEmfPayloadForCollection(array $metrics, array $dimensions): array
    {
        $emfLog = $this->createCollectionBaseEmfLog($dimensions);
        $emfLog = array_merge($emfLog, $dimensions);

        foreach ($metrics as $metric) {
            $emfLog['_aws']['CloudWatchMetrics'][0]['Metrics'][] = [
                'Name' => $metric->name(),
                'Unit' => $metric->unit()->value,
            ];
            $emfLog[$metric->name()] = $metric->value();
        }

        return $emfLog;
    }

    /**
     * @param array<string, string> $dimensions
     *
     * @return array<string, int|array<int, array<string, string|array<int, array<int, string>|array<string, string>>>>>
     */
    private function createBaseEmfLog(array $dimensions, string $metricName, string $unit): array
    {
        return [
            '_aws' => [
                'Timestamp' => $this->currentTimestamp(),
                'CloudWatchMetrics' => [
                    [
                        'Namespace' => $this->namespace,
                        'Dimensions' => [array_keys($dimensions)],
                        'Metrics' => [
                            ['Name' => $metricName, 'Unit' => $unit],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<string, string> $dimensions
     *
     * @return array<string, int|array<int, array<string, string|array<int, array<int, string>|array<int, never>>>>>
     */
    private function createCollectionBaseEmfLog(array $dimensions): array
    {
        return [
            '_aws' => [
                'Timestamp' => $this->currentTimestamp(),
                'CloudWatchMetrics' => [
                    [
                        'Namespace' => $this->namespace,
                        'Dimensions' => [array_keys($dimensions)],
                        'Metrics' => [],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<string, int|float|string|array<string, int|float|string|array<int|string, int|float|string|array<int|string, int|float|string|array<string, string>>>>> $emfLog
     */
    private function writeEmfLog(array $emfLog): void
    {
        $this->logger->info('', $emfLog);
    }

    private function currentTimestamp(): int
    {
        return (int) (microtime(true) * 1000);
    }
}
