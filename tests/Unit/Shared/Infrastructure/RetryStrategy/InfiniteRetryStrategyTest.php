<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\RetryStrategy;

use App\Shared\Application\Observability\Factory\RetryStrategyMetricFactory;
use App\Shared\Application\Observability\Metric\DlqRoutingMetric;
use App\Shared\Application\Observability\Metric\RetryAttemptMetric;
use App\Shared\Infrastructure\RetryStrategy\InfiniteRetryStrategy;
use App\Tests\Unit\Shared\Infrastructure\Observability\BusinessMetricsEmitterSpy;
use App\Tests\Unit\UnitTestCase;
use DomainException;
use InvalidArgumentException;
use JsonException;
use LogicException;
use RuntimeException;
use Symfony\Component\HttpClient\Response\AsyncContext;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Exception\ValidationFailedException as MessengerValidationFailedException;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException as ValidatorValidationFailedException;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Throwable;
use TypeError;
use ValueError;

final class InfiniteRetryStrategyTest extends UnitTestCase
{
    private const int DELAY_MS = 60000;

    private InfiniteRetryStrategy $retryStrategy;
    private BusinessMetricsEmitterSpy $metricsEmitter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->metricsEmitter = new BusinessMetricsEmitterSpy();
        $this->retryStrategy = new InfiniteRetryStrategy(
            self::DELAY_MS,
            $this->metricsEmitter,
            new RetryStrategyMetricFactory()
        );
    }

    public function testShouldRetry(): void
    {
        $context = $this->createMock(AsyncContext::class);

        $responseContent = null;
        $exception = null;

        $this->assertTrue(
            $this->retryStrategy->shouldRetry(
                $context,
                $responseContent,
                $exception
            )
        );
    }

    public function testGetDelay(): void
    {
        $context = $this->createMock(AsyncContext::class);

        $responseContent = null;
        $exception = null;

        $this->assertSame(
            self::DELAY_MS,
            $this->retryStrategy->getDelay(
                $context,
                $responseContent,
                $exception
            )
        );
    }

    public function testIsRetryableReturnsTrueForUnknownException(): void
    {
        $this->assertTrue($this->retryStrategy->isRetryable(
            $this->envelope(),
            new RuntimeException('backend unavailable')
        ));

        $this->assertEmittedMetric(
            RetryAttemptMetric::class,
            'MessengerRetryAttempts',
            'retry',
            RuntimeException::class
        );
    }

    public function testIsRetryableReturnsTrueForTransientTransportException(): void
    {
        $exception = new class('SQS timeout') extends RuntimeException implements TransportExceptionInterface {
        };

        $this->assertTrue($this->retryStrategy->isRetryable(
            $this->envelope(),
            $exception
        ));

        $this->assertEmittedMetric(
            RetryAttemptMetric::class,
            'MessengerRetryAttempts',
            'retry',
            $exception::class
        );
    }

    public function testRetryMetricReportsRootCauseForWrappedRetryableException(): void
    {
        $rootCause = new \Exception('connection reset');
        $throwable = new RuntimeException('handler failed', 0, $rootCause);

        $this->assertTrue($this->retryStrategy->isRetryable(
            $this->envelope(),
            $throwable
        ));

        $this->assertEmittedMetric(
            RetryAttemptMetric::class,
            'MessengerRetryAttempts',
            'retry',
            \Exception::class
        );
    }

    public function testIsRetryableReturnsTrueWithoutThrowable(): void
    {
        $this->assertTrue($this->retryStrategy->isRetryable($this->envelope()));

        $this->assertEmittedMetric(
            RetryAttemptMetric::class,
            'MessengerRetryAttempts',
            'retry',
            'None'
        );
    }

    public function testIsRetryableReturnsFalseForPermanentFailure(): void
    {
        foreach ($this->permanentFailureCases() as $throwable) {
            $this->metricsEmitter->clear();

            $this->assertFalse($this->retryStrategy->isRetryable(
                $this->envelope(),
                $throwable
            ));

            $this->assertEmittedMetric(
                DlqRoutingMetric::class,
                'MessengerDlqRoutings',
                'dlq',
                $throwable::class
            );
        }
    }

    public function testIsRetryableReturnsFalseForMessengerValidationFailure(): void
    {
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $throwable = new MessengerValidationFailedException(
            new \stdClass(),
            $violations,
            $this->envelope()
        );

        $this->assertFalse($this->retryStrategy->isRetryable(
            $this->envelope(),
            $throwable
        ));

        $this->assertEmittedMetric(
            DlqRoutingMetric::class,
            'MessengerDlqRoutings',
            'dlq',
            MessengerValidationFailedException::class
        );
    }

    public function testIsRetryableReturnsFalseForValidatorFailure(): void
    {
        $violations = $this->createMock(ConstraintViolationListInterface::class);
        $violations->method('__toString')->willReturn('Invalid message');
        $throwable = new ValidatorValidationFailedException(
            new \stdClass(),
            $violations
        );

        $this->assertFalse($this->retryStrategy->isRetryable(
            $this->envelope(),
            $throwable
        ));

        $this->assertEmittedMetric(
            DlqRoutingMetric::class,
            'MessengerDlqRoutings',
            'dlq',
            ValidatorValidationFailedException::class
        );
    }

    public function testIsRetryableInspectsWrappedPermanentFailure(): void
    {
        $wrapped = new TypeError('Invalid event payload');
        $throwable = new HandlerFailedException($this->envelope(), [$wrapped]);

        $this->assertFalse($this->retryStrategy->isRetryable(
            $this->envelope(),
            $throwable
        ));

        $this->assertEmittedMetric(
            DlqRoutingMetric::class,
            'MessengerDlqRoutings',
            'dlq',
            TypeError::class
        );
    }

    public function testDlqMetricReportsMatchedPermanentFailure(): void
    {
        $rootCause = new RuntimeException('database unavailable');
        $permanentFailure = new DomainException('Domain rule failed', 0, $rootCause);
        $throwable = new RuntimeException('handler failed', 0, $permanentFailure);

        $this->assertFalse($this->retryStrategy->isRetryable(
            $this->envelope(),
            $throwable
        ));

        $this->assertEmittedMetric(
            DlqRoutingMetric::class,
            'MessengerDlqRoutings',
            'dlq',
            DomainException::class
        );
    }

    public function testMetricsFailureDoesNotChangeRetryDecision(): void
    {
        $this->metricsEmitter->failOnNextCall();

        $this->assertTrue($this->retryStrategy->isRetryable(
            $this->envelope(),
            new RuntimeException('backend unavailable')
        ));
        $this->assertSame(0, $this->metricsEmitter->count());

        $this->metricsEmitter->failOnNextCall();

        $this->assertFalse($this->retryStrategy->isRetryable(
            $this->envelope(),
            new TypeError('Invalid payload')
        ));

        $this->assertSame(0, $this->metricsEmitter->count());
    }

    public function testGetWaitingTime(): void
    {
        $this->assertSame(
            self::DELAY_MS,
            $this->retryStrategy->getWaitingTime($this->envelope())
        );
    }

    /**
     * @return iterable<string, Throwable>
     */
    private function permanentFailureCases(): iterable
    {
        yield 'domain exception' => new DomainException('Domain rule failed');
        yield 'invalid argument' => new InvalidArgumentException('Invalid input');
        yield 'json exception' => new JsonException('Invalid JSON');
        yield 'logic exception' => new LogicException('Programmer error');
        yield 'message decoding' => new MessageDecodingFailedException('Bad envelope');
        yield 'serializer exception' => new NotEncodableValueException('Bad schema');
        yield 'type error' => new TypeError('Wrong type');
        yield 'value error' => new ValueError('Wrong value');
    }

    private function envelope(): Envelope
    {
        return new Envelope(new \stdClass());
    }

    /**
     * @param class-string $metricClass
     */
    private function assertEmittedMetric(
        string $metricClass,
        string $metricName,
        string $operation,
        string $exceptionType
    ): void {
        self::assertSame(1, $this->metricsEmitter->count());

        $metric = $this->metricsEmitter->emitted()->all()[0];
        self::assertInstanceOf($metricClass, $metric);
        self::assertSame($metricName, $metric->name());
        self::assertSame(1, $metric->value());
        self::assertSame('Messenger', $metric->dimensions()->values()->get('Endpoint'));
        self::assertSame($operation, $metric->dimensions()->values()->get('Operation'));
        self::assertSame('stdClass', $metric->dimensions()->values()->get('MessageType'));
        self::assertSame(
            $this->shortClassName($exceptionType),
            $metric->dimensions()->values()->get('ExceptionType')
        );
    }

    private function shortClassName(string $className): string
    {
        $parts = explode('\\', $className);

        return end($parts);
    }
}
