<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus;

use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\Shared\Infrastructure\Bus\MessageBusFactory;
use App\Tests\Unit\Shared\Infrastructure\Bus\Stub\TestMessage;
use App\Tests\Unit\Shared\Infrastructure\Bus\Stub\TestMessageReusableHandler;
use App\Tests\Unit\Shared\Infrastructure\Bus\Stub\TestOtherEvent;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final class MessageBusFactoryTest extends UnitTestCase
{
    private MessageBusFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new MessageBusFactory();
    }

    public function testCreateReturnsMessageBus(): void
    {
        $messageBus = $this->factory->create([]);

        self::assertInstanceOf(MessageBus::class, $messageBus);
    }

    public function testDispatchInvokesHandler(): void
    {
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

        $messageBus = $this->factory->create([$handler]);
        $messageBus->dispatch(new TestMessage());

        self::assertTrue($handler->wasCalled());
    }

    public function testDispatchWithMultipleHandlersForSameEvent(): void
    {
        $handler1 = new class() {
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

        $handler2 = new class() {
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

        $messageBus = $this->factory->create([$handler1, $handler2]);
        $messageBus->dispatch(new TestMessage());

        self::assertTrue($handler1->wasCalled());
        self::assertTrue($handler2->wasCalled());
    }

    public function testDomainEventSubscriberIsMappedToAllSubscribedEvents(): void
    {
        $received = [];

        $subscriber = new class($received) implements DomainEventSubscriberInterface {
            /** @param array<string> $received */
            public function __construct(private array &$received)
            {
            }

            public function subscribedTo(): array
            {
                return [TestMessage::class, TestOtherEvent::class];
            }

            public function __invoke(TestMessage|TestOtherEvent $message): void
            {
                $this->received[] = $message::class;
            }
        };

        $messageBus = $this->factory->create([$subscriber]);
        $messageBus->dispatch(new TestMessage());
        $messageBus->dispatch(new TestOtherEvent('event-id', null));

        self::assertSame([TestMessage::class, TestOtherEvent::class], $received);
    }

    public function testDispatchWithTwoInstancesOfSameHandlerClass(): void
    {
        $handled = [];

        $prototype = new class($handled) {
            /** @param array<int> $handled */
            public function __construct(private array &$handled)
            {
            }

            public function __invoke(TestMessage $message): void
            {
                $this->handled[] = spl_object_id($this);
            }
        };

        $handler1 = $prototype;
        $handler2 = clone $prototype;

        $messageBus = $this->factory->create([$handler1, $handler2]);
        $messageBus->dispatch(new TestMessage());

        self::assertCount(2, $handled);
        self::assertNotSame($handled[0], $handled[1]);
    }

    public function testDispatchRoutesToCorrectHandler(): void
    {
        $testMessageHandler = new class() {
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

        $otherEventHandler = new class() {
            private bool $called = false;

            public function __invoke(TestOtherEvent $event): void
            {
                $this->called = true;
            }

            public function wasCalled(): bool
            {
                return $this->called;
            }
        };

        $messageBus = $this->factory->create([$testMessageHandler, $otherEventHandler]);
        $messageBus->dispatch(new TestOtherEvent('event-id', null));

        self::assertFalse($testMessageHandler->wasCalled());
        self::assertTrue($otherEventHandler->wasCalled());
    }

    public function testHandlerWithoutTypedParameterIsNotMapped(): void
    {
        $noParamHandler = new class() {
            private bool $called = false;

            public function __invoke(): void
            {
                $this->called = true;
            }

            public function wasCalled(): bool
            {
                return $this->called;
            }
        };

        $testMessageHandler = new class() {
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

        $messageBus = $this->factory->create([$noParamHandler, $testMessageHandler]);
        $messageBus->dispatch(new TestMessage());

        self::assertFalse($noParamHandler->wasCalled());
        self::assertTrue($testMessageHandler->wasCalled());
    }

    public function testMiddlewareIsExecutedBeforeHandler(): void
    {
        $middlewareCalled = false;

        $middleware = new class($middlewareCalled) implements MiddlewareInterface {
            public function __construct(private bool &$called)
            {
            }

            public function handle(Envelope $envelope, StackInterface $stack): Envelope
            {
                $this->called = true;

                return $stack->next()->handle($envelope, $stack);
            }
        };

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

        $factory = new MessageBusFactory([$middleware]);
        $messageBus = $factory->create([$handler]);
        $messageBus->dispatch(new TestMessage());

        self::assertTrue($middlewareCalled);
        self::assertTrue($handler->wasCalled());
    }

    public function testMiddlewareFromGeneratorIsExecuted(): void
    {
        $middlewareCalled = false;

        $middleware = new class($middlewareCalled) implements MiddlewareInterface {
            public function __construct(private bool &$called)
            {
            }

            public function handle(Envelope $envelope, StackInterface $stack): Envelope
            {
                $this->called = true;

                return $stack->next()->handle($envelope, $stack);
            }
        };

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

        /** @var \Generator<MiddlewareInterface> $middlewareGenerator */
        $middlewareGenerator = (static function () use ($middleware): \Generator {
            yield $middleware;
        })();

        $factory = new MessageBusFactory($middlewareGenerator);
        $messageBus = $factory->create([$handler]);
        $messageBus->dispatch(new TestMessage());

        self::assertTrue($middlewareCalled);
        self::assertTrue($handler->wasCalled());
    }

    public function testMultipleSameClassHandlersAllReceiveEvents(): void
    {
        $counter = new \stdClass();
        $counter->value = 0;

        $handler1 = new TestMessageReusableHandler($counter);
        $handler2 = new TestMessageReusableHandler($counter);
        $handler3 = new TestMessageReusableHandler($counter);

        $messageBus = $this->factory->create([$handler1, $handler2, $handler3]);
        $messageBus->dispatch(new TestMessage());

        // All 3 handlers should be called due to unique alias generation
        self::assertSame(3, $counter->value);
    }
}
