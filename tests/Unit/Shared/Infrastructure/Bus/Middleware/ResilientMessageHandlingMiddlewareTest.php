<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Middleware;

use App\Shared\Infrastructure\Bus\MessageBusFactory;
use App\Shared\Infrastructure\Bus\Middleware\ResilientMessageHandlingMiddleware;
use App\Tests\Unit\Shared\Infrastructure\Bus\Stub\TestMessage;
use App\Tests\Unit\UnitTestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

final class ResilientMessageHandlingMiddlewareTest extends UnitTestCase
{
    public function testPassesThroughWhenHandlerSucceeds(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('error');

        $middleware = new ResilientMessageHandlingMiddleware($logger);

        $handler = new class() {
            private bool $called = false;

            public function __invoke(TestMessage $message): void
            {
                $this->called = true;
            }

            public function wasCalled(): bool
            {
                return $this->called;
            }
        };

        $bus = (new MessageBusFactory([$middleware]))->create([$handler]);
        $bus->dispatch(new TestMessage());

        self::assertTrue($handler->wasCalled());
    }

    public function testSwallowsHandlerFailureAndLogsWrappedException(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('error')
            ->with(
                'Event subscriber execution failed',
                self::callback(static function (array $context): bool {
                    $hasWrappedException = isset($context['wrapped_exceptions'])
                        && count($context['wrapped_exceptions']) > 0;

                    if (! $hasWrappedException) {
                        return false;
                    }

                    $wrappedException = array_values($context['wrapped_exceptions'])[0];

                    // Verify ALL keys exist in wrapped exception
                    return ($context['message_class'] ?? null) === TestMessage::class
                        && ($context['exception_class'] ?? null) === HandlerFailedException::class
                        && isset($wrappedException['message'])
                        && isset($wrappedException['exception_class'])
                        && isset($wrappedException['trace'])
                        && $wrappedException['message'] === 'Connection failed';
                })
            );

        $middleware = new ResilientMessageHandlingMiddleware($logger);

        $handler = new class() {
            public function __invoke(TestMessage $message): void
            {
                throw new \RuntimeException('Connection failed');
            }
        };

        $bus = (new MessageBusFactory([$middleware]))->create([$handler]);

        // must not throw
        $bus->dispatch(new TestMessage());

        self::assertTrue(true);
    }

    public function testSwallowsDirectThrowableAndLogs(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('error')
            ->with(
                'Event subscriber execution failed',
                self::callback(static function (array $context): bool {
                    return ($context['message_class'] ?? null) === TestMessage::class
                        && ($context['exception_class'] ?? null) === \RuntimeException::class
                        && ($context['error'] ?? null) === 'Boom'
                        && ! isset($context['wrapped_exceptions']);
                })
            );

        $middleware = new ResilientMessageHandlingMiddleware($logger);

        $stack = new class() implements \Symfony\Component\Messenger\Middleware\StackInterface {
            public function next(): \Symfony\Component\Messenger\Middleware\MiddlewareInterface
            {
                return new class() implements \Symfony\Component\Messenger\Middleware\MiddlewareInterface {
                    public function handle(
                        \Symfony\Component\Messenger\Envelope $envelope,
                        \Symfony\Component\Messenger\Middleware\StackInterface $stack
                    ): \Symfony\Component\Messenger\Envelope {
                        throw new \RuntimeException('Boom');
                    }
                };
            }
        };

        $envelope = new \Symfony\Component\Messenger\Envelope(new TestMessage());

        // must not throw
        $middleware->handle($envelope, $stack);

        self::assertTrue(true);
    }

    public function testAddsDomainEventContext(): void
    {
        $event = new class('event-id', null) extends \App\Shared\Domain\Bus\Event\DomainEvent {
            public static function fromPrimitives(array $body, string $eventId, string $occurredOn): self
            {
                return new self($eventId, $occurredOn);
            }

            public static function eventName(): string
            {
                return 'test.domain_event';
            }

            public function toPrimitives(): array
            {
                return ['foo' => 'bar'];
            }
        };

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('error')
            ->with(
                'Event subscriber execution failed',
                self::callback(fn (array $context): bool => $this->assertDomainEventContextIsComplete($context))
            );

        $middleware = new ResilientMessageHandlingMiddleware($logger);

        $handler = new class() {
            public function __invoke(\App\Shared\Domain\Bus\Event\DomainEvent $event): void
            {
                throw new \RuntimeException('Boom');
            }
        };

        $bus = (new MessageBusFactory([$middleware]))->create([$handler]);

        // must not throw
        $bus->dispatch($event);

        self::assertTrue(true);
    }

    private function assertDomainEventContextIsComplete(array $context): bool
    {
        $hasBaseContext = isset($context['message_class'], $context['error'], $context['exception_class'], $context['trace']);

        $hasDomainContext = isset($context['event_name'], $context['event_id'], $context['occurred_on'], $context['payload'])
            && $context['event_name'] === 'test.domain_event'
            && $context['event_id'] === 'event-id'
            && $context['payload']['foo'] === 'bar';

        return $hasBaseContext && $hasDomainContext;
    }
}
