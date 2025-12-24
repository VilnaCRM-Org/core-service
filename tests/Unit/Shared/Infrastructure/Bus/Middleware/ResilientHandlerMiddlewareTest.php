<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Middleware;

use App\Shared\Infrastructure\Bus\Middleware\ResilientHandlerMiddleware;
use App\Tests\Unit\UnitTestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final class ResilientHandlerMiddlewareTest extends UnitTestCase
{
    public function testPassesThroughWhenNoException(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('error');

        $middleware = new ResilientHandlerMiddleware($logger);

        $message = new \stdClass();
        $envelope = new Envelope($message);

        $nextMiddleware = $this->createMock(MiddlewareInterface::class);
        $nextMiddleware->expects(self::once())
            ->method('handle')
            ->willReturn($envelope);

        $stack = $this->createMock(StackInterface::class);
        $stack->expects(self::once())
            ->method('next')
            ->willReturn($nextMiddleware);

        $result = $middleware->handle($envelope, $stack);

        self::assertSame($envelope, $result);
    }

    public function testCatchesExceptionAndLogsError(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('error')
            ->with(
                'Event subscriber execution failed',
                self::callback(static function (array $context): bool {
                    return isset($context['message_class'])
                        && isset($context['error'])
                        && isset($context['exception_class'])
                        && isset($context['trace']);
                })
            );

        $middleware = new ResilientHandlerMiddleware($logger);

        $message = new \stdClass();
        $envelope = new Envelope($message);

        $exception = new \RuntimeException('Test exception');
        $nextMiddleware = $this->createMock(MiddlewareInterface::class);
        $nextMiddleware->expects(self::once())
            ->method('handle')
            ->willThrowException($exception);

        $stack = $this->createMock(StackInterface::class);
        $stack->expects(self::once())
            ->method('next')
            ->willReturn($nextMiddleware);

        $result = $middleware->handle($envelope, $stack);

        self::assertSame($envelope, $result);
    }

    public function testLogsCorrectMessageClass(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('error')
            ->with(
                'Event subscriber execution failed',
                self::callback(static function (array $context): bool {
                    return $context['message_class'] === \stdClass::class
                        && $context['error'] === 'Specific error message'
                        && $context['exception_class'] === \InvalidArgumentException::class;
                })
            );

        $middleware = new ResilientHandlerMiddleware($logger);

        $message = new \stdClass();
        $envelope = new Envelope($message);

        $exception = new \InvalidArgumentException('Specific error message');
        $nextMiddleware = $this->createMock(MiddlewareInterface::class);
        $nextMiddleware->expects(self::once())
            ->method('handle')
            ->willThrowException($exception);

        $stack = $this->createMock(StackInterface::class);
        $stack->expects(self::once())
            ->method('next')
            ->willReturn($nextMiddleware);

        $middleware->handle($envelope, $stack);
    }
}
