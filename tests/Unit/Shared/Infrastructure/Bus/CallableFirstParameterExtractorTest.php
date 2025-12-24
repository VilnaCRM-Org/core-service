<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\Shared\Infrastructure\Bus\CallableFirstParameterExtractor;
use App\Shared\Infrastructure\Bus\HandlersLocatorMapBuilder;
use App\Tests\Unit\Shared\Infrastructure\Bus\Stub\TestDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Bus\Stub\TestOtherEvent;
use App\Tests\Unit\Shared\Infrastructure\Bus\Stub\TestOtherEventSubscriber;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Assert;

final class CallableFirstParameterExtractorTest extends UnitTestCase
{
    private CallableFirstParameterExtractor $extractor;

    protected function setUp(): void
    {
        $this->extractor = new CallableFirstParameterExtractor();
    }

    public function testExtractForCallables(): void
    {
        $subscriber = $this->createDomainEventSubscriber();
        $callables = [$subscriber];
        $expected = [DomainEvent::class => $callables];

        $extracted = HandlersLocatorMapBuilder::fromHandlers($callables);

        $this->assertEquals($expected, $extracted);
    }

    public function testExtractForCallablesWithMultipleSubscribersForSameEvent(): void
    {
        $subscriber1 = $this->createDomainEventSubscriber();
        $subscriber2 = $this->createDomainEventSubscriber();
        $callables = [$subscriber1, $subscriber2];

        $extracted = HandlersLocatorMapBuilder::fromHandlers($callables);

        $this->assertMultipleSubscribers($extracted, $subscriber1, $subscriber2);
    }

    public function testExtractForCallablesSkipsCallablesWithoutTypedParameter(): void
    {
        $subscriber = $this->createNoParameterSubscriber();

        $extracted = HandlersLocatorMapBuilder::fromHandlers([$subscriber]);

        self::assertEmpty($extracted);
    }

    public function testExtractForCallablesWithMultipleDifferentEventTypes(): void
    {
        $subscriber1 = new TestDomainEventSubscriber();
        $subscriber2 = new TestOtherEventSubscriber();
        $callables = [$subscriber1, $subscriber2];

        $extracted = HandlersLocatorMapBuilder::fromHandlers($callables);

        $this->assertDifferentEventTypes($extracted, $subscriber1, $subscriber2);
    }

    public function testExtract(): void
    {
        $subscriberClass = $this->createDomainEventSubscriber();

        $extracted = $this->extractor->extract($subscriberClass);

        $this->assertEquals(DomainEvent::class, $extracted);
    }

    public function testExtractWithError(): void
    {
        $subscriberClass = $this->createUntypedParameterSubscriber();

        $this->expectException(\LogicException::class);

        $this->extractor->extract($subscriberClass);
    }

    public function testExtractThrowsWhenCallableHasNoInvokeMethod(): void
    {
        $subscriberClass = $this->createNonInvokableSubscriber();

        $this->expectException(\LogicException::class);

        $this->extractor->extract($subscriberClass);
    }

    public function testExtractThrowsForBuiltinFirstParameterType(): void
    {
        $subscriberClass = $this->createBuiltinTypeSubscriber();

        $this->expectException(\LogicException::class);

        $this->extractor->extract($subscriberClass);
    }

    public function testExtractThrowsForUnionFirstParameterType(): void
    {
        $subscriberClass = $this->createUnionTypeSubscriber();

        $this->expectException(\LogicException::class);

        $this->extractor->extract($subscriberClass);
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

    private function createUntypedParameterSubscriber(): DomainEventSubscriberInterface
    {
        return new class() implements DomainEventSubscriberInterface {
            /**
             * @return array<class-string>
             */
            public function subscribedTo(): array
            {
                return [DomainEvent::class];
            }

            /**
             * @param object $someClass
             */
            public function __invoke($someClass): void
            {
                Assert::assertNotNull($someClass);
            }
        };
    }

    private function createNonInvokableSubscriber(): DomainEventSubscriberInterface
    {
        return new class() implements DomainEventSubscriberInterface {
            /**
             * @return array<class-string>
             */
            public function subscribedTo(): array
            {
                return [DomainEvent::class];
            }
        };
    }

    private function createBuiltinTypeSubscriber(): DomainEventSubscriberInterface
    {
        return new class() implements DomainEventSubscriberInterface {
            /**
             * @return array<class-string>
             */
            public function subscribedTo(): array
            {
                return [DomainEvent::class];
            }

            public function __invoke(string $event): void
            {
                Assert::assertNotNull($event);
            }
        };
    }

    private function createUnionTypeSubscriber(): DomainEventSubscriberInterface
    {
        return new class() implements DomainEventSubscriberInterface {
            /**
             * @return array<class-string>
             */
            public function subscribedTo(): array
            {
                return [DomainEvent::class];
            }

            public function __invoke(DomainEvent|TestOtherEvent $event): void
            {
                Assert::assertNotNull($event);
            }
        };
    }

    /**
     * @param array<string, array<DomainEventSubscriberInterface>> $extracted
     */
    private function assertMultipleSubscribers(
        array $extracted,
        DomainEventSubscriberInterface $subscriber1,
        DomainEventSubscriberInterface $subscriber2
    ): void {
        self::assertCount(1, $extracted);
        self::assertArrayHasKey(DomainEvent::class, $extracted);
        self::assertCount(2, $extracted[DomainEvent::class]);
        self::assertSame($subscriber1, $extracted[DomainEvent::class][0]);
        self::assertSame($subscriber2, $extracted[DomainEvent::class][1]);
    }

    /**
     * @param array<string, array<DomainEventSubscriberInterface>> $extracted
     */
    private function assertDifferentEventTypes(
        array $extracted,
        DomainEventSubscriberInterface $subscriber1,
        DomainEventSubscriberInterface $subscriber2
    ): void {
        self::assertCount(2, $extracted);
        self::assertArrayHasKey(DomainEvent::class, $extracted);
        self::assertArrayHasKey(TestOtherEvent::class, $extracted);
        self::assertCount(1, $extracted[DomainEvent::class]);
        self::assertCount(1, $extracted[TestOtherEvent::class]);
        self::assertSame($subscriber1, $extracted[DomainEvent::class][0]);
        self::assertSame($subscriber2, $extracted[TestOtherEvent::class][0]);
    }
}
