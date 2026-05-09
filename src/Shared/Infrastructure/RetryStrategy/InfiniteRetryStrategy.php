<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\RetryStrategy;

use App\Shared\Application\Observability\Emitter\BusinessMetricsEmitterInterface;
use App\Shared\Application\Observability\Metric\BusinessMetric;
use App\Shared\Application\Observability\Metric\DlqRoutingMetric;
use App\Shared\Application\Observability\Metric\RetryAttemptMetric;
use DomainException;
use InvalidArgumentException;
use JsonException;
use LogicException;
use Symfony\Component\HttpClient\Response\AsyncContext;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Exception\ValidationFailedException as MessengerValidationFailure;
use Symfony\Component\Messenger\Retry\RetryStrategyInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException as ValidatorValidationFailure;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Throwable;
use TypeError;
use ValueError;

final class InfiniteRetryStrategy implements RetryStrategyInterface
{
    /**
     * @var list<class-string<Throwable>>
     */
    private const PERMANENT_FAILURES = [
        DomainException::class,
        InvalidArgumentException::class,
        JsonException::class,
        LogicException::class,
        MessageDecodingFailedException::class,
        MessengerValidationFailure::class,
        SerializerExceptionInterface::class,
        TypeError::class,
        ValidatorValidationFailure::class,
        ValueError::class,
    ];

    public function __construct(
        private readonly int $delayMs,
        private readonly BusinessMetricsEmitterInterface $metricsEmitter,
    ) {
    }

    public function shouldRetry(
        AsyncContext $_context,
        ?string $_responseContent,
        ?TransportExceptionInterface $_exception
    ): ?bool {
        return true;
    }

    public function getDelay(
        AsyncContext $_context,
        ?string $_responseContent,
        ?TransportExceptionInterface $_exception
    ): int {
        return $this->delayMs;
    }

    public function isRetryable(
        Envelope $message,
        ?Throwable $throwable = null
    ): bool {
        $permanentFailure = $this->permanentFailure($throwable);
        if ($permanentFailure instanceof Throwable) {
            $this->emitDlqRouting($message, $permanentFailure);

            return false;
        }

        $this->emitRetryAttempt($message, $throwable);

        return true;
    }

    public function getWaitingTime(
        Envelope $message,
        ?Throwable $throwable = null
    ): int {
        return $this->delayMs;
    }

    private function permanentFailure(?Throwable $throwable): ?Throwable
    {
        while ($throwable instanceof Throwable) {
            if ($this->isPermanentFailure($throwable)) {
                return $throwable;
            }

            $throwable = $throwable->getPrevious();
        }

        return null;
    }

    private function isPermanentFailure(Throwable $throwable): bool
    {
        foreach (self::PERMANENT_FAILURES as $permanentFailure) {
            if ($throwable instanceof $permanentFailure) {
                return true;
            }
        }

        return false;
    }

    private function emitRetryAttempt(Envelope $message, ?Throwable $throwable): void
    {
        $this->emitMetric(RetryAttemptMetric::create(
            $this->messageType($message),
            $this->exceptionType($throwable)
        ));
    }

    private function emitDlqRouting(Envelope $message, ?Throwable $throwable): void
    {
        $this->emitMetric(DlqRoutingMetric::create(
            $this->messageType($message),
            $this->matchedExceptionType($throwable)
        ));
    }

    private function emitMetric(BusinessMetric $metric): void
    {
        try {
            $this->metricsEmitter->emit($metric);
        } catch (Throwable) {
            // Retry decisions must not depend on observability availability.
            return;
        }
    }

    private function messageType(Envelope $message): string
    {
        return $message->getMessage()::class;
    }

    private function exceptionType(?Throwable $throwable): string
    {
        $rootCause = $this->rootCause($throwable);

        return $rootCause instanceof Throwable ? $rootCause::class : 'None';
    }

    private function matchedExceptionType(?Throwable $throwable): string
    {
        return $throwable instanceof Throwable ? $throwable::class : 'None';
    }

    private function rootCause(?Throwable $throwable): ?Throwable
    {
        while ($throwable?->getPrevious() instanceof Throwable) {
            $throwable = $throwable->getPrevious();
        }

        return $throwable;
    }
}
