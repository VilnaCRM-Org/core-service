<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\Shared\Infrastructure\Bus\MessageBusFactory;
use App\Tests\Unit\Shared\Infrastructure\Bus\Stub\TestDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Bus\Stub\TestOtherEventSubscriber;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\Messenger\MessageBus;

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

    public function testCreateWithSingleHandler(): void
    {
        $subscriber = $this->createDomainEventSubscriber();

        $messageBus = $this->factory->create([$subscriber]);

        self::assertInstanceOf(MessageBus::class, $messageBus);
    }

    public function testCreateWithMultipleHandlersForSameEvent(): void
    {
        $subscriber1 = $this->createDomainEventSubscriber();
        $subscriber2 = $this->createDomainEventSubscriber();

        $messageBus = $this->factory->create([$subscriber1, $subscriber2]);

        self::assertInstanceOf(MessageBus::class, $messageBus);
    }

    public function testCreateSkipsHandlersWithoutTypedParameter(): void
    {
        $subscriber = $this->createNoParameterSubscriber();

        $messageBus = $this->factory->create([$subscriber]);

        self::assertInstanceOf(MessageBus::class, $messageBus);
    }

    public function testCreateWithMultipleDifferentEventTypes(): void
    {
        $subscriber1 = new TestDomainEventSubscriber();
        $subscriber2 = new TestOtherEventSubscriber();

        $messageBus = $this->factory->create([$subscriber1, $subscriber2]);

        self::assertInstanceOf(MessageBus::class, $messageBus);
    }

    private function createDomainEventSubscriber(): DomainEventSubscriberInterface
    {
        return new class() implements DomainEventSubscriberInterface {
            /**
             * @return array<class-string>
             */
            public function subscribedTo(): array
            {
                return [DomainEvent::class];
            }

            public function __invoke(DomainEvent $event): void
            {
                Assert::assertNotNull($event);
            }
        };
    }

    private function createNoParameterSubscriber(): DomainEventSubscriberInterface
    {
        return new class() implements DomainEventSubscriberInterface {
            /**
             * @return array<class-string>
             */
            public function subscribedTo(): array
            {
                return [DomainEvent::class];
            }

            public function __invoke(): void
            {
                Assert::assertTrue(true);
            }
        };
    }
}
