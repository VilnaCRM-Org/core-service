<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability;

use App\Shared\Application\Observability\BusinessMetricsEmitterInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

final readonly class AwsEmfBusinessMetricsEmitter implements BusinessMetricsEmitterInterface
{
    private const NAMESPACE = 'CCore/BusinessMetrics';

    public function __construct(
        private string $output = 'php://stdout',
        private ?LoggerInterface $logger = null
    ) {
    }

    /**
     * @param array<string, string> $dimensions
     */
    public function emit(
        string $metricName,
        float|int $value,
        array $dimensions = [],
        string $unit = 'Count'
    ): void {
        $timestamp = (int) (microtime(true) * 1000);

        $emfLog = [
            '_aws' => [
                'Timestamp' => $timestamp,
                'CloudWatchMetrics' => [
                    [
                        'Namespace' => self::NAMESPACE,
                        'Dimensions' => [array_keys($dimensions)],
                        'Metrics' => [
                            ['Name' => $metricName, 'Unit' => $unit],
                        ],
                    ],
                ],
            ],
            $metricName => $value,
        ];

        $emfLog += $dimensions;

        $this->write($emfLog);
    }

    /**
     * @param array<string, array{value: float|int, unit?: string}> $metrics
     * @param array<string, string> $dimensions
     */
    public function emitMultiple(array $metrics, array $dimensions = []): void
    {
        $emfLog = $this->createBaseEmfLog($dimensions);

        foreach ($metrics as $name => $config) {
            $emfLog['_aws']['CloudWatchMetrics'][0]['Metrics'][] = [
                'Name' => $name,
                'Unit' => $config['unit'] ?? 'Count',
            ];
            $emfLog[$name] = $config['value'];
        }

        $emfLog += $dimensions;

        $this->write($emfLog);
    }

    /**
     * @param array<string, string> $dimensions
     *
     * @return array<string, bool|int|float|string|array|null>
     */
    private function createBaseEmfLog(array $dimensions): array
    {
        $timestamp = (int) (microtime(true) * 1000);

        return [
            '_aws' => [
                'Timestamp' => $timestamp,
                'CloudWatchMetrics' => [
                    [
                        'Namespace' => self::NAMESPACE,
                        'Dimensions' => [array_keys($dimensions)],
                        'Metrics' => [],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<string, bool|int|float|string|array|null> $emfLog
     */
    private function write(array $emfLog): void
    {
        try {
            $json = json_encode($emfLog, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->logger?->error('Failed to encode EMF log to JSON', [
                'exception' => $e->getMessage(),
            ]);

            return;
        }

        $this->writeToOutput($json);
    }

    private function writeToOutput(string $json): void
    {
        set_error_handler(static function (int $severity, string $message): never {
            throw new RuntimeException($message);
        });

        try {
            file_put_contents($this->output, $json . "\n", FILE_APPEND);
        } finally {
            restore_error_handler();
        }
    }
}
