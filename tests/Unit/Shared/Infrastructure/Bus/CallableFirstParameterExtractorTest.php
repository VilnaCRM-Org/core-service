<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\Shared\Infrastructure\Bus\CallableFirstParameterExtractor;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Assert;

final class CallableFirstParameterExtractorTest extends UnitTestCase
{
    public function testForCallables(): void
    {
        $subscriber = $this->createSubscriber('MyEvent');
        $callables = [$subscriber];

        $extractor = CallableFirstParameterExtractor::forCallables($callables);
        $result = $extractor->indexed();

        $expected = ['MyEvent' => [$subscriber]];
        $this->assertEquals($expected, $result);
    }

    public function testForPimpleCallables(): void
    {
        $subscriber1 = $this->createSubscriber('MyEvent1');
        $subscriber2 = $this->createSubscriber('MyEvent2');
        $callables = [$subscriber1, $subscriber2];

        $extractor = CallableFirstParameterExtractor::forPimpleCallables($callables);
        $result = $extractor->indexed();

        $expected = [
            'MyEvent1' => [$subscriber1],
            'MyEvent2' => [$subscriber2],
        ];
        $this->assertEquals($expected, $result);
    }

    public function testExtractFromDomainEventSubscriber(): void
    {
        $subscriber = $this->createDomainEventSubscriber();
        $extractor = CallableFirstParameterExtractor::forCallables([$subscriber]);

        $mockEvent = $this->createMock(DomainEvent::class);
        $result = $extractor->extract($subscriber, $mockEvent);

        $this->assertEquals(DomainEvent::class, $result);
    }

    public function testExtractFromClosure(): void
    {
        $closure = static function (DomainEvent $event): void {
            Assert::assertNotNull($event);
        };

        $extractor = CallableFirstParameterExtractor::forCallables([$closure]);
        $mockEvent = $this->createMock(DomainEvent::class);
        $result = $extractor->extract($closure, $mockEvent);

        $this->assertEquals(DomainEvent::class, $result);
    }

    public function testExtractFromInvokableClass(): void
    {
        $invokable = new class() {
            public function __invoke(DomainEvent $event): void
            {
                Assert::assertNotNull($event);
            }
        };

        $extractor = CallableFirstParameterExtractor::forCallables([$invokable]);
        $mockEvent = $this->createMock(DomainEvent::class);
        $result = $extractor->extract($invokable, $mockEvent);

        $this->assertEquals(DomainEvent::class, $result);
    }

    public function testIndexedSkipsNullResults(): void
    {
        $validSubscriber = $this->createSubscriber('ValidEvent');
        $invalidSubscriber = new class() {
        };

        $extractor = CallableFirstParameterExtractor::forCallables([
            $validSubscriber,
            $invalidSubscriber
        ]);
        $result = $extractor->indexed();

        $this->assertArrayHasKey('ValidEvent', $result);
        $this->assertCount(1, $result);
    }

    private function createSubscriber(string $eventClass): DomainEventSubscriberInterface
    {
        return new class($eventClass) implements DomainEventSubscriberInterface {
            public function __construct(private string $eventClass)
            {
            }

            public function subscribedTo(): array
            {
                return [$this->createMockEvent()];
            }

            public function __invoke(): void
            {
                Assert::assertTrue(true);
            }

            private function createMockEvent(): object
            {
                return new class($this->eventClass) {
                    public function __construct(private string $className)
                    {
                    }

                    public function __get(string $name): string
                    {
                        if ($name === 'class') {
                            return $this->className;
                        }
                        return '';
                    }
                };
            }
        };
    }

    private function createDomainEventSubscriber(): DomainEventSubscriberInterface
    {
        return new class() implements DomainEventSubscriberInterface {
            public function subscribedTo(): array
            {
                $mockEvent = $this->createMock(DomainEvent::class);
                return [$mockEvent];
            }

            public function __invoke(DomainEvent $event): void
            {
                Assert::assertNotNull($event);
            }

            private function createMock(string $className): object
            {
                return new class($className) {
                    public function __construct(string $className)
                    {
                    }
                };
            }
        };
    }
}
