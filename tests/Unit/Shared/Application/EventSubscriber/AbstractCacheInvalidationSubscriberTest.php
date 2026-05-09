<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\EventSubscriber;

use App\Shared\Application\Command\CacheInvalidationCommand;
use App\Shared\Application\CommandHandler\CacheInvalidationCommandHandler;
use App\Shared\Application\DTO\CacheInvalidationTagSet;
use App\Shared\Infrastructure\Collection\CacheRefreshCommandCollection;
use App\Tests\Unit\Shared\Application\EventSubscriber\Stub\ExposedCacheInvalidationSubscriber;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

final class AbstractCacheInvalidationSubscriberTest extends UnitTestCase
{
    private CacheInvalidationCommandHandler&MockObject $handler;
    private LoggerInterface&MockObject $logger;
    private ExposedCacheInvalidationSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = $this->createMock(CacheInvalidationCommandHandler::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->subscriber = new ExposedCacheInvalidationSubscriber(
            $this->handler,
            $this->logger
        );
    }

    public function testInvalidateBuildsDomainEventInvalidationCommand(): void
    {
        $tags = CacheInvalidationTagSet::create('customer.1');

        $this->handler
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(self::assertDomainEventCommand(...)));

        $this->subscriber->callInvalidate(
            'customer',
            'updated',
            $tags,
            CacheRefreshCommandCollection::create()
        );
    }

    public function testInvalidateLogsWarningWhenHandlerFails(): void
    {
        $this->handler
            ->expects($this->once())
            ->method('__invoke')
            ->willThrowException(new \RuntimeException('handler failed'));

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'Domain-event cache invalidation failed',
                $this->callback(self::assertFailureContext(...))
            );

        $this->subscriber->callInvalidate(
            'customer',
            'updated',
            CacheInvalidationTagSet::create('customer.1'),
            CacheRefreshCommandCollection::create()
        );
    }

    private static function assertDomainEventCommand(CacheInvalidationCommand $command): bool
    {
        return $command->context() === 'customer'
            && $command->source() === 'domain_event'
            && $command->operation() === 'updated'
            && iterator_to_array($command->tags()) === ['customer.1'];
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function assertFailureContext(array $context): bool
    {
        return $context['operation'] === 'cache.invalidation.error'
            && $context['cache_operation'] === 'updated'
            && $context['context'] === 'customer'
            && $context['error'] === 'handler failed'
            && $context['exception'] instanceof \Throwable;
    }
}
