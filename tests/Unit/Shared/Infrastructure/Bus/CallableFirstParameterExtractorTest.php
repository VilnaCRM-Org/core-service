<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\Shared\Infrastructure\Bus\CallableFirstParameterExtractor;
use App\Tests\Unit\Shared\Infrastructure\Bus\Stub\TestOtherEvent;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Assert;

final class CallableFirstParameterExtractorTest extends UnitTestCase
{
    private CallableFirstParameterExtractor $extractor;

    protected function setUp(): void
    {
        $this->extractor = new CallableFirstParameterExtractor();
    }

    public function testExtract(): void
    {
        $subscriberClass = $this->createDomainEventSubscriber();

        $extracted = $this->extractor->extract($subscriberClass);

        $this->assertEquals(DomainEvent::class, $extracted);
    }

    public function testExtractReturnsNullForMissingTypeHint(): void
    {
        $subscriberClass = $this->createUntypedParameterSubscriber();

        self::assertNull($this->extractor->extract($subscriberClass));
    }

    public function testExtractReturnsNullWhenCallableHasNoInvokeMethod(): void
    {
        $subscriberClass = $this->createNonInvokableSubscriber();

        self::assertNull($this->extractor->extract($subscriberClass));
    }

    public function testExtractReturnsNullForBuiltinFirstParameterType(): void
    {
        $subscriberClass = $this->createBuiltinTypeSubscriber();

        self::assertNull($this->extractor->extract($subscriberClass));
    }

    public function testExtractReturnsNullForUnionFirstParameterType(): void
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
}
