<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus;

use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\Shared\Infrastructure\Bus\MessageBusFactory;
use App\Tests\Unit\Shared\Infrastructure\Bus\Stub\TestMessage;
use App\Tests\Unit\Shared\Infrastructure\Bus\Stub\TestOtherEvent;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Messenger\MessageBus;

/**
 * Tests for MessageBusFactory following user-service patterns
 *
 * Note: user-service's implementation has these characteristics:
 * - No middleware injection in constructor
 * - Uses forCallables() which maps one handler per message type via __invoke parameter
 * - For DomainEventSubscriberInterface, routing is based on __invoke type, not subscribedTo()
 */
final class MessageBusFactoryTest extends UnitTestCase
{
    private MessageBusFactory $factory;

    #[\Override]
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

    public function testDomainEventSubscriberIsMappedToAllSubscribedEvents(): void
    {
        $received = [];

        $subscriber = new class($received) implements DomainEventSubscriberInterface {
            /** @param array<string> $received */
            public function __construct(private array &$received)
            {
            }

            #[\Override]
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

    public function testHandlerWithoutInvokeMethodIsNotMapped(): void
    {
        $noInvokeHandler = new class() {
            private bool $called = false;

            public function handle(TestMessage $message): void
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

        $messageBus = $this->factory->create([$noInvokeHandler, $testMessageHandler]);
        $messageBus->dispatch(new TestMessage());

        self::assertFalse($noInvokeHandler->wasCalled());
        self::assertTrue($testMessageHandler->wasCalled());
    }

    public function testHandlerWithMultipleParametersIsNotMapped(): void
    {
        $multiParamHandler = new class() {
            private bool $called = false;

            public function __invoke(TestMessage $message, string $extra): void
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

        $messageBus = $this->factory->create([$multiParamHandler, $testMessageHandler]);
        $messageBus->dispatch(new TestMessage());

        self::assertFalse($multiParamHandler->wasCalled());
        self::assertTrue($testMessageHandler->wasCalled());
    }

    public function testMixedSubscribersAndRegularHandlersRoutedCorrectly(): void
    {
        $subscriberCalls = [];
        $regularHandlerCalls = [];

        // Subscriber: uses subscribedTo() for routing to MULTIPLE events
        $subscriber = new class($subscriberCalls) implements DomainEventSubscriberInterface {
            /** @param array<string> $calls */
            public function __construct(private array &$calls)
            {
            }

            #[\Override]
            public function subscribedTo(): array
            {
                return [TestMessage::class]; // subscribes to TestMessage
            }

            public function __invoke(TestMessage $message): void
            {
                $this->calls[] = 'subscriber';
            }
        };

        // Regular handler: uses __invoke parameter for routing
        $regularHandler = new class($regularHandlerCalls) {
            /** @param array<string> $calls */
            public function __construct(private array &$calls)
            {
            }

            public function __invoke(TestOtherEvent $event): void
            {
                $this->calls[] = 'regular';
            }
        };

        $messageBus = $this->factory->create([$subscriber, $regularHandler]);

        // Dispatch TestMessage - should trigger subscriber
        $messageBus->dispatch(new TestMessage());
        self::assertContains('subscriber', $subscriberCalls);
        self::assertNotContains('regular', $regularHandlerCalls);

        // Dispatch TestOtherEvent - should trigger regular handler
        $messageBus->dispatch(new TestOtherEvent('event-id', null));
        self::assertContains('regular', $regularHandlerCalls);
    }

    public function testRegularHandlerIsNotTreatedAsSubscriber(): void
    {
        $regularHandlerCalls = [];

        // Regular handler (NOT a DomainEventSubscriber) - should use __invoke param
        $regularHandler = new class($regularHandlerCalls) {
            /** @param array<string> $calls */
            public function __construct(private array &$calls)
            {
            }

            public function __invoke(TestMessage $message): void
            {
                $this->calls[] = 'regular';
            }
        };

        $messageBus = $this->factory->create([$regularHandler]);
        $messageBus->dispatch(new TestMessage());

        // Regular handler should be called via __invoke parameter mapping
        self::assertContains('regular', $regularHandlerCalls);
    }

    public function testSubscriberRoutedBySubscribedToNotInvokeParameter(): void
    {
        $subscriberCalls = [];

        // Subscriber subscribedTo() returns BOTH TestMessage AND TestOtherEvent
        // This proves routing uses subscribedTo(), not __invoke parameter
        $subscriber = new class($subscriberCalls) implements DomainEventSubscriberInterface {
            /** @param array<string> $calls */
            public function __construct(private array &$calls)
            {
            }

            #[\Override]
            public function subscribedTo(): array
            {
                // Subscribes to BOTH events, proving subscribedTo() is used
                return [TestMessage::class, TestOtherEvent::class];
            }

            public function __invoke(TestMessage|TestOtherEvent $event): void
            {
                $this->calls[] = $event::class;
            }
        };

        $messageBus = $this->factory->create([$subscriber]);

        // Dispatch TestMessage - should be handled (in subscribedTo())
        $messageBus->dispatch(new TestMessage());
        self::assertContains(TestMessage::class, $subscriberCalls);

        // Dispatch TestOtherEvent - should also be handled (in subscribedTo())
        $messageBus->dispatch(new TestOtherEvent('event-id', null));
        self::assertContains(TestOtherEvent::class, $subscriberCalls);
    }

    #[\Override]
    public function testNoHandlerExceptionWhenEventNotSubscribed(): void
    {
        $subscriber = new class() implements DomainEventSubscriberInterface {
            public function subscribedTo(): array
            {
                return [TestOtherEvent::class]; // Only subscribes to TestOtherEvent!
            }

            public function __invoke(TestOtherEvent $event): void
            {
                // This handler should not be called for TestMessage
            }
        };

        $messageBus = $this->factory->create([$subscriber]);

        // Dispatch TestMessage - no handler registered, should throw
        $this->expectException(\Symfony\Component\Messenger\Exception\NoHandlerForMessageException::class);
        $messageBus->dispatch(new TestMessage());
    }

    /**
     * Verifies that subscribers are NOT incorrectly mapped as regular handlers.
     *
     * This test creates a subscriber that:
     * - subscribedTo() returns TestMessage (should be mapped to TestMessage)
     * - __invoke takes TestOtherEvent (would map to TestOtherEvent if treated as regular handler)
     *
     * With correct filtering: subscriber is ONLY in subscriber map (maps to TestMessage)
     * With mutation: subscriber is ALSO in regular handler map (maps to TestOtherEvent)
     *
     * So dispatching TestOtherEvent should NOT call the subscriber (it only subscribes to TestMessage).
     * But if the mutation occurs, the subscriber would be mapped to TestOtherEvent via __invoke.
     */
    public function testSubscriberNotMappedAsRegularHandlerByInvokeParam(): void
    {
        $subscriberCalled = false;

        // Subscriber: subscribedTo() returns TestMessage, but __invoke takes TestOtherEvent
        // This means routing MUST use subscribedTo(), not __invoke parameter
        $subscriber = new class($subscriberCalled) implements DomainEventSubscriberInterface {
            public function __construct(private bool &$called)
            {
            }

            #[\Override]
            public function subscribedTo(): array
            {
                return [TestMessage::class]; // Subscribes to TestMessage ONLY
            }

            public function __invoke(TestOtherEvent $event): void
            {
                // __invoke takes TestOtherEvent - NOT what we subscribe to
                // If incorrectly mapped as regular handler, this would be called for TestOtherEvent
                $this->called = true;
            }
        };

        $messageBus = $this->factory->create([$subscriber]);

        // Dispatch TestOtherEvent - subscriber should NOT handle this
        // (it subscribes to TestMessage, not TestOtherEvent)
        // But if mutation makes subscriber also go through forCallables(),
        // it would be mapped to TestOtherEvent via __invoke param type
        $this->expectException(\Symfony\Component\Messenger\Exception\NoHandlerForMessageException::class);
        $messageBus->dispatch(new TestOtherEvent('test-id', null));
    }
}
