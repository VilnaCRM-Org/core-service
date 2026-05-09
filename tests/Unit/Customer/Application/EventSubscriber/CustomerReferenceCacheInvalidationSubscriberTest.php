<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\EventSubscriber;

use App\Core\Customer\Application\EventSubscriber\CustomerReferenceCacheInvalidationSubscriber;
use App\Core\Customer\Domain\Event\CustomerStatusCreatedEvent;
use App\Core\Customer\Domain\Event\CustomerStatusUpdatedEvent;
use App\Core\Customer\Domain\Event\CustomerTypeCreatedEvent;
use App\Core\Customer\Domain\Event\CustomerTypeUpdatedEvent;
use App\Core\Customer\Infrastructure\Collection\CustomerCacheInvalidationRuleCollection;
use App\Shared\Application\Command\CacheInvalidationCommand;
use App\Shared\Application\CommandHandler\CacheInvalidationCommandHandler;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

final class CustomerReferenceCacheInvalidationSubscriberTest extends UnitTestCase
{
    private CacheInvalidationCommandHandler&MockObject $handler;
    private CustomerReferenceCacheInvalidationSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = $this->createMock(CacheInvalidationCommandHandler::class);
        $this->subscriber = new CustomerReferenceCacheInvalidationSubscriber(
            $this->handler,
            $this->createMock(LoggerInterface::class)
        );
    }

    public function testSubscribedToIncludesReferenceEvents(): void
    {
        self::assertSame([
            CustomerStatusCreatedEvent::class,
            CustomerStatusUpdatedEvent::class,
            CustomerTypeCreatedEvent::class,
            CustomerTypeUpdatedEvent::class,
        ], $this->subscriber->subscribedTo());
    }

    public function testCreatedEventInvalidatesReferenceTags(): void
    {
        $this->handler
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(
                self::assertInvalidationCommand(
                    CustomerCacheInvalidationRuleCollection::OPERATION_CREATED
                )
            ));

        ($this->subscriber)(
            new CustomerStatusCreatedEvent('status-id', 'active')
        );
    }

    public function testUpdatedEventInvalidatesReferenceTags(): void
    {
        $this->handler
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(
                self::assertInvalidationCommand(
                    CustomerCacheInvalidationRuleCollection::OPERATION_UPDATED
                )
            ));

        ($this->subscriber)(
            new CustomerTypeUpdatedEvent('type-id', 'business', 'individual')
        );
    }

    private static function assertInvalidationCommand(string $operation): callable
    {
        return static function (CacheInvalidationCommand $command) use ($operation): bool {
            return $command->context() === 'customer'
                && $command->source() === 'domain_event'
                && $command->operation() === $operation
                && iterator_to_array($command->tags()) === [
                    'customer.collection',
                    'customer.reference',
                ]
                && count($command->refreshCommands()) === 0;
        };
    }
}
