<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\EventDispatcher;

use App\Tests\Unit\Shared\Infrastructure\EventDispatcher\Stub\ConcreteResilientEventSubscriber;
use App\Tests\Unit\UnitTestCase;
use Psr\Log\LoggerInterface;

final class ResilientEventSubscriberTest extends UnitTestCase
{
    public function testSafeExecuteDoesNotThrowWhenHandlerSucceeds(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('error');

        $subscriber = new ConcreteResilientEventSubscriber($logger);

        $executed = false;
        $subscriber->executeSafely(static function () use (&$executed): void {
            $executed = true;
        });

        self::assertTrue($executed);
    }

    public function testSafeExecuteCatchesExceptionAndLogs(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('error')
            ->with(
                'Event subscriber execution failed',
                self::callback(static function (array $context): bool {
                    return $context['subscriber'] === ConcreteResilientEventSubscriber::class
                        && $context['event'] === 'test.event'
                        && $context['error'] === 'Test exception'
                        && isset($context['trace']);
                })
            );

        $subscriber = new ConcreteResilientEventSubscriber($logger);

        $subscriber->executeSafely(static function (): void {
            throw new \RuntimeException('Test exception');
        });
    }

    public function testSafeExecuteAllowsProcessingToContinueAfterException(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error');

        $subscriber = new ConcreteResilientEventSubscriber($logger);

        $callCount = 0;

        // First call throws
        $subscriber->executeSafely(static function () use (&$callCount): void {
            $callCount++;
            throw new \RuntimeException('First call failed');
        });

        // Second call should still execute
        $subscriber->executeSafely(static function () use (&$callCount): void {
            $callCount++;
        });

        self::assertSame(2, $callCount);
    }

    public function testSafeExecuteCatchesThrowableNotJustExceptions(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('error')
            ->with(
                'Event subscriber execution failed',
                self::callback(static function (array $context): bool {
                    return str_contains($context['error'], 'must be of type int');
                })
            );

        $subscriber = new ConcreteResilientEventSubscriber($logger);

        // Trigger a TypeError by calling a function with wrong argument type
        $subscriber->executeSafely(static function (): void {
            // This will trigger a TypeError: argument must be of type int, string given
            $func = static function (int $value): void {
            };
            $func('invalid'); // @phpstan-ignore-line
        });
    }
}
