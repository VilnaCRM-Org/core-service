<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\Shared\Infrastructure\Bus\InvokeParameterExtractor;
use App\Tests\Unit\Shared\Infrastructure\Bus\Stub\TestOtherEvent;
use App\Tests\Unit\Shared\Infrastructure\Bus\Stub\UntypedParameterSubscriber;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Assert;

final class InvokeParameterExtractorTest extends UnitTestCase
{
    private InvokeParameterExtractor $extractor;

    #[\Override]
    protected function setUp(): void
    {
        $this->extractor = new InvokeParameterExtractor();
    }

    public function testExtract(): void
    {
        $subscriberClass = $this->createDomainEventSubscriber();

        $extracted = $this->extractor->extract($subscriberClass);

        $this->assertEquals(DomainEvent::class, $extracted);
    }

    public function testExtractThrowsForMissingTypeHint(): void
    {
        $subscriber = new UntypedParameterSubscriber();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Missing type hint for the first parameter of __invoke');

        $this->extractor->extract($subscriber);
    }

    public function testExtractReturnsNullWhenCallableHasNoInvokeMethod(): void
    {
        $subscriberClass = $this->createNonInvokableSubscriber();

        self::assertNull($this->extractor->extract($subscriberClass));
    }

    public function testExtractReturnsTypeForBuiltinFirstParameterType(): void
    {
        $subscriberClass = $this->createBuiltinTypeSubscriber();

        // User-service returns the builtin type name (doesn't filter)
        self::assertSame('string', $this->extractor->extract($subscriberClass));
    }

    public function testExtractReturnsNullForMultipleParameters(): void
    {
        $subscriberClass = $this->createMultipleParametersSubscriber();

        self::assertNull($this->extractor->extract($subscriberClass));
    }

    public function testExtractReturnsNullForUnionTypeParameter(): void
    {
        $subscriberClass = $this->createUnionTypeSubscriber();

        self::assertNull($this->extractor->extract($subscriberClass));
    }

    private function createDomainEventSubscriber(): DomainEventSubscriberInterface
    {
        return new class() implements DomainEventSubscriberInterface {
            /**
             * @return array<class-string>
             */
            #[\Override]
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

    private function createNonInvokableSubscriber(): DomainEventSubscriberInterface
    {
        return new class() implements DomainEventSubscriberInterface {
            /**
             * @return array<class-string>
             */
            #[\Override]
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
            #[\Override]
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

    private function createMultipleParametersSubscriber(): DomainEventSubscriberInterface
    {
        return new class() implements DomainEventSubscriberInterface {
            /**
             * @return array<class-string>
             */
            #[\Override]
            public function subscribedTo(): array
            {
                return [DomainEvent::class];
            }

            public function __invoke(DomainEvent $event, string $extra): void
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
            #[\Override]
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
}
