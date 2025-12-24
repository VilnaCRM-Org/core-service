<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Event;

use App\Shared\Infrastructure\Bus\Event\ResilientDomainEventSubscriberDecorator;
use App\Tests\Unit\Shared\Infrastructure\Bus\Event\Stub\TestDomainEvent;
use App\Tests\Unit\Shared\Infrastructure\Bus\Event\Stub\TestDomainEventSubscriber;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

final class ResilientDomainEventSubscriberDecoratorTest extends UnitTestCase
{
    private LoggerInterface&MockObject $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testSubscribedToReturnsInnerSubscriberEvents(): void
    {
        $expectedEvents = [TestDomainEvent::class];
        $innerSubscriber = new TestDomainEventSubscriber();
        $decorator = new ResilientDomainEventSubscriberDecorator($innerSubscriber, $this->logger);

        $result = $decorator->subscribedTo();

        self::assertSame($expectedEvents, $result);
    }

    public function testInvokeCallsInnerSubscriberWhenNoException(): void
    {
        $event = new TestDomainEvent('test-id', 'test-value');
        $called = false;

        $innerSubscriber = new TestDomainEventSubscriber(static function () use (&$called): void {
            $called = true;
        });
        $decorator = new ResilientDomainEventSubscriberDecorator($innerSubscriber, $this->logger);

        $this->logger
            ->expects(self::never())
            ->method('error');

        ($decorator)($event);

        self::assertTrue($called, 'Inner subscriber should have been called');
    }

    public function testInvokeCatchesExceptionAndLogsError(): void
    {
        $event = new TestDomainEvent('test-id', 'test-value');
        $exception = new \RuntimeException('Metrics emission failed');

        $innerSubscriber = new TestDomainEventSubscriber(
            static fn () => throw $exception
        );
        $decorator = new ResilientDomainEventSubscriberDecorator($innerSubscriber, $this->logger);

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with(
                'Domain event subscriber execution failed',
                self::callback(fn (array $context) => $this->assertContextIsValid($context, $event, $exception))
            );

        // Should not throw - exception is swallowed
        ($decorator)($event);
    }

    public function testInvokeCatchesThrowableNotJustExceptions(): void
    {
        $event = new TestDomainEvent('test-id', 'test-value');

        $innerSubscriber = new TestDomainEventSubscriber(static function (): void {
            $func = static function (int $value): void {
            };
            $func('invalid'); // @phpstan-ignore-line - Triggers TypeError
        });
        $decorator = new ResilientDomainEventSubscriberDecorator($innerSubscriber, $this->logger);

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with(
                'Domain event subscriber execution failed',
                self::callback(static function (array $context): bool {
                    return isset($context['exception_class'])
                        && str_contains($context['exception_class'], 'TypeError');
                })
            );

        // Should not throw - error is swallowed
        ($decorator)($event);
    }

    public function testInvokeLogsCompleteContextWithAllEventDetails(): void
    {
        $event = new TestDomainEvent('event-123', 'payload-value');
        $exception = new \DomainException('Subscriber failed');

        $innerSubscriber = new TestDomainEventSubscriber(
            static fn () => throw $exception
        );
        $decorator = new ResilientDomainEventSubscriberDecorator($innerSubscriber, $this->logger);

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with(
                'Domain event subscriber execution failed',
                self::callback(static function (array $context) use ($event, $exception): bool {
                    // Verify all required keys exist
                    $requiredKeys = [
                        'subscriber',
                        'event',
                        'event_id',
                        'error',
                        'exception_class',
                        'trace',
                        'occurred_on',
                        'payload',
                    ];

                    foreach ($requiredKeys as $key) {
                        if (! isset($context[$key])) {
                            return false;
                        }
                    }

                    // Verify correct values
                    return $context['subscriber'] === TestDomainEventSubscriber::class
                        && $context['event'] === TestDomainEvent::eventName()
                        && $context['event_id'] === $event->eventId()
                        && $context['error'] === $exception->getMessage()
                        && $context['exception_class'] === \DomainException::class
                        && $context['occurred_on'] === $event->occurredOn()
                        && $context['payload'] === $event->toPrimitives();
                })
            );

        ($decorator)($event);
    }

    private function assertContextIsValid(array $context, TestDomainEvent $event, \Throwable $exception): bool
    {
        return isset($context['subscriber'], $context['event'], $context['trace'])
            && $context['subscriber'] === TestDomainEventSubscriber::class
            && $context['event'] === TestDomainEvent::eventName()
            && $context['event_id'] === $event->eventId()
            && $context['error'] === $exception->getMessage()
            && $context['exception_class'] === $exception::class
            && $context['occurred_on'] === $event->occurredOn()
            && $context['payload'] === $event->toPrimitives();
    }
}
