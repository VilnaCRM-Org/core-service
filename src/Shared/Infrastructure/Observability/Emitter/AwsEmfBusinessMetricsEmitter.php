<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\Emitter;

use App\Shared\Application\Observability\BusinessMetricsEmitterInterface;
use App\Shared\Application\Observability\Metric\BusinessMetric;
use App\Shared\Application\Observability\Metric\MetricCollection;
use App\Shared\Infrastructure\Observability\Factory\EmfPayloadFactoryInterface;
use App\Shared\Infrastructure\Observability\Formatter\EmfLogFormatter;
use App\Shared\Infrastructure\Observability\ValueObject\EmfPayload;
use Psr\Log\LoggerInterface;

/**
 * AWS EMF (Embedded Metric Format) Business Metrics Emitter
 *
 * Emits business metrics in AWS EMF format via Symfony logger.
 * CloudWatch automatically extracts metrics from EMF-formatted logs.
 */
final readonly class AwsEmfBusinessMetricsEmitter implements BusinessMetricsEmitterInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private EmfLogFormatter $emfLogFormatter,
        private EmfPayloadFactoryInterface $payloadFactory
    ) {
    }

    public function emit(BusinessMetric $metric): void
    {
        $payload = $this->payloadFactory->createFromMetric($metric);

        $this->writeEmfLog($payload);
    }

    public function emitCollection(MetricCollection $metrics): void
    {
        if ($metrics->isEmpty()) {
            return;
        }

        $payload = $this->payloadFactory->createFromCollection($metrics);

        $this->writeEmfLog($payload);
    }

    private function writeEmfLog(EmfPayload $payload): void
    {
        $formatted = $this->emfLogFormatter->format($payload);
        if ($formatted === '') {
            return;
        }

        $this->logger->info($formatted);
    }
}
