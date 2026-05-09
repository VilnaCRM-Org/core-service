<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\CommandHandler;

use App\Shared\Application\Command\CacheInvalidationCommand;
use App\Shared\Application\Command\CacheRefreshCommand;
use App\Shared\Application\CommandHandler\CacheInvalidationCommandHandler;
use App\Shared\Application\DTO\CacheInvalidationTagSet;
use App\Shared\Application\DTO\CacheRefreshPolicy;
use App\Shared\Application\Observability\Metric\CacheRefreshScheduledMetric;
use App\Shared\Application\Observability\Metric\ValueObject\MetricDimension;
use App\Shared\Application\Resolver\CachePoolResolverInterface;
use App\Shared\Infrastructure\Collection\CacheRefreshCommandCollection;
use App\Tests\Unit\Shared\Infrastructure\Observability\BusinessMetricsEmitterSpy;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class CacheInvalidationCommandHandlerTest extends UnitTestCase
{
    private TagAwareCacheInterface&MockObject $cache;
    private CachePoolResolverInterface&MockObject $cachePoolResolver;
    private MessageBusInterface&MockObject $messageBus;
    private LoggerInterface&MockObject $logger;
    private BusinessMetricsEmitterSpy $metricsEmitter;
    private CacheInvalidationCommandHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = $this->createMock(TagAwareCacheInterface::class);
        $this->cachePoolResolver = $this->createMock(CachePoolResolverInterface::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->metricsEmitter = new BusinessMetricsEmitterSpy();
        $this->handler = new CacheInvalidationCommandHandler(
            $this->cachePoolResolver,
            $this->messageBus,
            $this->logger,
            $this->metricsEmitter
        );
    }

    public function testInvalidatesTagsDispatchesRefreshCommandsAndEmitsScheduledMetrics(): void
    {
        $context = $this->faker->word();
        $family = $this->faker->word();
        $refreshSource = CacheRefreshPolicy::SOURCE_REPOSITORY_REFRESH;
        $refreshCommand = $this->refreshCommand($context, $family, $refreshSource);
        $invalidateOnlyCommand = $this->refreshCommand(
            $context,
            $family,
            CacheRefreshPolicy::SOURCE_INVALIDATE_ONLY
        );
        $tags = ['cache.first', 'cache.second'];
        $command = CacheInvalidationCommand::create(
            $context,
            $this->faker->word(),
            $this->faker->word(),
            CacheInvalidationTagSet::create(...$tags),
            CacheRefreshCommandCollection::create($invalidateOnlyCommand, $refreshCommand)
        );

        $this->expectTagInvalidation($tags, true, $context);
        $this->expectRefreshDispatch($refreshCommand);
        $this->logger->expects($this->never())->method('warning');

        self::assertTrue($this->handler->tryHandle($command));

        $this->assertScheduledMetric($context, $family, $refreshSource);
    }

    public function testEmptyTagsSkipCacheInvalidationButStillDispatchRefreshCommands(): void
    {
        $refreshCommand = $this->refreshCommand(
            $this->faker->word(),
            $this->faker->word(),
            CacheRefreshPolicy::SOURCE_EVENT_SNAPSHOT
        );
        $command = CacheInvalidationCommand::create(
            $this->faker->word(),
            $this->faker->word(),
            $this->faker->word(),
            CacheInvalidationTagSet::create(),
            CacheRefreshCommandCollection::create($refreshCommand)
        );

        $this->cache->expects($this->never())->method('invalidateTags');
        $this->cachePoolResolver->expects($this->never())->method('resolve');
        $this->expectRefreshDispatch($refreshCommand);

        self::assertTrue($this->handler->tryHandle($command));

        self::assertSame(1, $this->metricsEmitter->count());
    }

    public function testLogsWarningWhenTagInvalidationReturnsFalse(): void
    {
        $command = $this->invalidationCommandWithTags('customer.1');

        $this->expectTagInvalidation(['customer.1'], false);
        $this->expectInvalidationWarning('Cache invalidation returned false', $command);

        self::assertFalse($this->handler->tryHandle($command));
    }

    public function testLogsWarningWhenTagInvalidationThrows(): void
    {
        $command = $this->invalidationCommandWithTags('customer.1');

        $this->cachePoolResolver
            ->expects($this->once())
            ->method('resolve')
            ->with('customer')
            ->willReturn($this->cache);

        $this->cache
            ->expects($this->once())
            ->method('invalidateTags')
            ->willThrowException(new \RuntimeException('redis unavailable'));

        $this->expectInvalidationWarning(
            'Cache invalidation failed',
            $command,
            'redis unavailable'
        );

        ($this->handler)($command);
    }

    public function testLogsWarningWhenRefreshSchedulingThrows(): void
    {
        $refreshCommand = $this->refreshCommand(
            'customer',
            'detail',
            CacheRefreshPolicy::SOURCE_REPOSITORY_REFRESH
        );
        $command = $this->invalidationCommandWithRefresh($refreshCommand);

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($refreshCommand)
            ->willThrowException(new \RuntimeException('sqs unavailable'));

        $this->expectScheduleWarning($refreshCommand, 'sqs unavailable');

        ($this->handler)($command);
    }

    public function testLogsWarningWhenScheduledMetricEmissionThrows(): void
    {
        $refreshCommand = $this->refreshCommand(
            'customer',
            'detail',
            CacheRefreshPolicy::SOURCE_REPOSITORY_REFRESH
        );
        $command = $this->invalidationCommandWithRefresh($refreshCommand);
        $this->metricsEmitter->failOnNextCall();

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($refreshCommand)
            ->willReturn(new Envelope($refreshCommand));

        $this->expectMetricScheduleWarning($refreshCommand);

        ($this->handler)($command);
    }

    private function refreshCommand(
        string $context,
        string $family,
        string $refreshSource
    ): CacheRefreshCommand {
        return CacheRefreshCommand::create(
            $context,
            $family,
            $this->faker->word(),
            (string) $this->faker->ulid(),
            $refreshSource,
            $this->faker->word(),
            (string) $this->faker->ulid(),
            (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
        );
    }

    private function invalidationCommandWithTags(string ...$tags): CacheInvalidationCommand
    {
        return CacheInvalidationCommand::create(
            'customer',
            'domain_event',
            'updated',
            CacheInvalidationTagSet::create(...$tags),
            CacheRefreshCommandCollection::create()
        );
    }

    private function invalidationCommandWithRefresh(
        CacheRefreshCommand $refreshCommand
    ): CacheInvalidationCommand {
        return CacheInvalidationCommand::create(
            'customer',
            'domain_event',
            'updated',
            CacheInvalidationTagSet::create(),
            CacheRefreshCommandCollection::create($refreshCommand)
        );
    }

    /**
     * @param list<string> $tags
     */
    private function expectTagInvalidation(
        array $tags,
        bool $result,
        string $context = 'customer'
    ): void {
        $this->cachePoolResolver
            ->expects($this->once())
            ->method('resolve')
            ->with($context)
            ->willReturn($this->cache);

        $this->cache
            ->expects($this->once())
            ->method('invalidateTags')
            ->with($tags)
            ->willReturn($result);
    }

    private function expectRefreshDispatch(CacheRefreshCommand $command): void
    {
        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($command)
            ->willReturn(new Envelope($command));
    }

    private function assertScheduledMetric(
        string $context,
        string $family,
        string $refreshSource
    ): void {
        self::assertSame(1, $this->metricsEmitter->count());
        $metric = $this->metricsEmitter->emitted()->all()[0];
        self::assertInstanceOf(CacheRefreshScheduledMetric::class, $metric);
        $this->metricsEmitter->assertEmittedWithDimensions(
            'CacheRefreshScheduled',
            new MetricDimension('Context', $context),
            new MetricDimension('Family', $family),
            new MetricDimension('Source', $refreshSource),
            new MetricDimension('Result', 'scheduled')
        );
    }

    private function expectInvalidationWarning(
        string $message,
        CacheInvalidationCommand $command,
        ?string $error = null
    ): void {
        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                $message,
                $this->callback(
                    fn (array $context): bool => $this->assertInvalidationContext(
                        $context,
                        $command,
                        $error
                    )
                )
            );
    }

    /**
     * @param array<string, mixed> $context
     */
    private function assertInvalidationContext(
        array $context,
        CacheInvalidationCommand $command,
        ?string $error
    ): bool {
        self::assertSame('cache.invalidation.error', $context['operation']);
        self::assertSame('customer', $context['context']);
        self::assertSame('domain_event', $context['source']);
        self::assertSame($command->dedupeKey(), $context['dedupe_key']);
        self::assertSame($error, $context['error'] ?? null);
        if ($error !== null) {
            self::assertInstanceOf(\Throwable::class, $context['exception']);
        }

        return true;
    }

    private function expectScheduleWarning(CacheRefreshCommand $command, string $error): void
    {
        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'Cache refresh scheduling failed',
                $this->callback(fn (array $context): bool => $this->assertScheduleContext(
                    $context,
                    $command,
                    $error
                ))
            );
    }

    /**
     * @param array<string, mixed> $context
     */
    private function assertScheduleContext(
        array $context,
        CacheRefreshCommand $command,
        string $error
    ): bool {
        self::assertSame('cache.refresh.schedule_error', $context['operation']);
        self::assertSame('customer', $context['context']);
        self::assertSame('detail', $context['family']);
        self::assertSame($command->dedupeKey(), $context['dedupe_key']);
        self::assertSame($error, $context['error']);
        self::assertInstanceOf(\Throwable::class, $context['exception']);

        return true;
    }

    private function expectMetricScheduleWarning(CacheRefreshCommand $command): void
    {
        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'Cache refresh scheduling failed',
                $this->callback(fn (array $context): bool => $this->assertMetricContext(
                    $context,
                    $command
                ))
            );
    }

    /**
     * @param array<string, mixed> $context
     */
    private function assertMetricContext(array $context, CacheRefreshCommand $command): bool
    {
        self::assertSame('cache.refresh.schedule_error', $context['operation']);
        self::assertSame($command->dedupeKey(), $context['dedupe_key']);
        self::assertSame('Metric emission failed', $context['error']);
        self::assertInstanceOf(\Throwable::class, $context['exception']);

        return true;
    }
}
