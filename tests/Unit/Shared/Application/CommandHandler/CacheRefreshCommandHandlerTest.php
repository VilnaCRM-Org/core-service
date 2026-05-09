<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\CommandHandler;

use App\Shared\Application\Command\CacheRefreshCommand;
use App\Shared\Application\CommandHandler\CacheRefreshCommandHandler;
use App\Shared\Application\DTO\CacheRefreshPolicy;
use App\Shared\Application\Observability\Metric\CacheRefreshFailedMetric;
use App\Shared\Application\Observability\Metric\CacheRefreshSucceededMetric;
use App\Shared\Application\Observability\Metric\ValueObject\MetricDimension;
use App\Shared\Application\Resolver\CachePoolResolverInterface;
use App\Shared\Infrastructure\Resolver\CacheRefreshCommandHandlerResolver;
use App\Tests\Unit\Shared\Application\CommandHandler\Stub\FailingRefreshHandler;
use App\Tests\Unit\Shared\Application\CommandHandler\Stub\RecordingRefreshHandler;
use App\Tests\Unit\Shared\Infrastructure\Observability\BusinessMetricsEmitterSpy;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class CacheRefreshCommandHandlerTest extends UnitTestCase
{
    private LoggerInterface&MockObject $logger;
    private CachePoolResolverInterface&MockObject $cachePoolResolver;
    private TagAwareCacheInterface&MockObject $dedupeCache;
    private BusinessMetricsEmitterSpy $metricsEmitter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->cachePoolResolver = $this->createMock(CachePoolResolverInterface::class);
        $this->dedupeCache = $this->createMock(TagAwareCacheInterface::class);
        $this->metricsEmitter = new BusinessMetricsEmitterSpy();
    }

    public function testRoutesToContextSpecificHandlerAndEmitsSucceededMetric(): void
    {
        $context = $this->faker->word();
        $family = $this->faker->word();
        $source = CacheRefreshPolicy::SOURCE_REPOSITORY_REFRESH;
        $command = $this->refreshCommand($context, $family, $source);
        $unsupportedHandler = new RecordingRefreshHandler(
            self::unsupportedContext($context),
            false
        );
        $matchingHandler = new RecordingRefreshHandler($context, true);
        $handler = $this->handlerWith($unsupportedHandler, $matchingHandler);

        $this->expectDedupeClaim($command);

        $handler($command);

        self::assertSame(0, $unsupportedHandler->calls());
        self::assertSame(1, $matchingHandler->calls());
        self::assertSame($command, $matchingHandler->lastCommand());
        self::assertSame(1, $this->metricsEmitter->count());
        self::assertInstanceOf(
            CacheRefreshSucceededMetric::class,
            $this->metricsEmitter->emitted()->all()[0]
        );
        $this->metricsEmitter->assertEmittedWithDimensions(
            'CacheRefreshSucceeded',
            new MetricDimension('Context', $context),
            new MetricDimension('Family', $family),
            new MetricDimension('Source', $source),
            new MetricDimension('Result', 'succeeded')
        );
    }

    public function testSkippedRefreshDoesNotEmitMetric(): void
    {
        $context = $this->faker->word();
        $command = $this->refreshCommand(
            $context,
            $this->faker->word(),
            CacheRefreshPolicy::SOURCE_REPOSITORY_REFRESH
        );
        $handler = $this->handlerWith(new RecordingRefreshHandler($context, false));

        $this->expectDedupeClaim($command);

        $handler($command);

        self::assertSame(0, $this->metricsEmitter->count());
    }

    public function testDuplicateRefreshCommandIsSkippedByDedupeMarker(): void
    {
        $context = $this->faker->word();
        $command = $this->refreshCommand(
            $context,
            $this->faker->word(),
            CacheRefreshPolicy::SOURCE_REPOSITORY_REFRESH
        );
        $matchingHandler = new RecordingRefreshHandler($context, true);
        $handler = $this->handlerWith($matchingHandler);

        $this->expectDedupeHit($command);

        $handler($command);

        self::assertSame(0, $matchingHandler->calls());
        self::assertSame(0, $this->metricsEmitter->count());
    }

    public function testRefreshContinuesWhenDedupeMarkerIsUnavailable(): void
    {
        $context = $this->faker->word();
        $family = $this->faker->word();
        $source = CacheRefreshPolicy::SOURCE_REPOSITORY_REFRESH;
        $command = $this->refreshCommand($context, $family, $source);
        $matchingHandler = new RecordingRefreshHandler($context, true);
        $handler = $this->handlerWith($matchingHandler);

        $this->cachePoolResolver
            ->expects($this->once())
            ->method('resolve')
            ->with($context)
            ->willThrowException(new \RuntimeException('cache unavailable'));
        $this->expectDedupeUnavailableLog($command);

        $handler($command);

        self::assertSame(1, $matchingHandler->calls());
        self::assertSame(1, $this->metricsEmitter->count());
    }

    public function testFailureIsLoggedMeasuredAndRethrown(): void
    {
        $context = $this->faker->word();
        $family = $this->faker->word();
        $source = CacheRefreshPolicy::SOURCE_EVENT_SNAPSHOT;
        $command = $this->refreshCommand($context, $family, $source);
        $handler = new CacheRefreshCommandHandler(
            new CacheRefreshCommandHandlerResolver([new FailingRefreshHandler($context)]),
            $this->cachePoolResolver,
            $this->logger,
            $this->metricsEmitter
        );

        $this->expectDedupeClaim($command, releaseOnFailure: true);
        $this->expectFailureLog($command);

        $this->assertRefreshFailureIsRethrown($handler, $command);

        $this->assertFailedMetricWasEmitted($context, $family, $source);
    }

    public function testRefreshFailureLogsDedupeMarkerReleaseFailure(): void
    {
        $context = $this->faker->word();
        $command = $this->refreshCommand(
            $context,
            $this->faker->word(),
            CacheRefreshPolicy::SOURCE_EVENT_SNAPSHOT
        );
        $handler = new CacheRefreshCommandHandler(
            new CacheRefreshCommandHandlerResolver([new FailingRefreshHandler($context)]),
            $this->cachePoolResolver,
            $this->logger,
            $this->metricsEmitter
        );

        $this->expectDedupeClaimWithReleaseFailure($command);
        $this->expectDedupeReleaseAndRefreshFailureLogs($command);

        $this->assertRefreshFailureIsRethrown($handler, $command);

        self::assertSame(1, $this->metricsEmitter->count());
    }

    public function testResolverThrowsForUnsupportedContext(): void
    {
        $context = $this->faker->word();
        $resolver = new CacheRefreshCommandHandlerResolver([
            new RecordingRefreshHandler(self::unsupportedContext($context), true),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'No cache refresh handler registered for context "%s".',
            $context
        ));

        $resolver->resolve($context);
    }

    public function testResolverReturnsMatchingHandler(): void
    {
        $context = $this->faker->word();
        $matchingHandler = new RecordingRefreshHandler($context, true);
        $resolver = new CacheRefreshCommandHandlerResolver([
            new RecordingRefreshHandler(self::unsupportedContext($context), false),
            $matchingHandler,
        ]);

        self::assertSame($matchingHandler, $resolver->resolve($context));
    }

    public function testAbstractHandlerSkipsUnsupportedContextBeforeRefreshing(): void
    {
        $handler = new RecordingRefreshHandler('customer', true);
        $command = $this->refreshCommand(
            'invoice',
            'detail',
            CacheRefreshPolicy::SOURCE_REPOSITORY_REFRESH
        );

        $result = $handler($command);

        self::assertSame(0, $handler->calls());
        self::assertFalse($result->refreshed());
        self::assertSame('unsupported_context', $result->reason());
    }

    public function testAbstractHandlerSkipsInvalidateOnlyRefreshCommands(): void
    {
        $handler = new RecordingRefreshHandler('customer', true);
        $command = $this->refreshCommand(
            'customer',
            'detail',
            CacheRefreshPolicy::SOURCE_INVALIDATE_ONLY
        );

        $result = $handler($command);

        self::assertSame(0, $handler->calls());
        self::assertFalse($result->refreshed());
        self::assertSame('invalidate_only', $result->reason());
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

    private static function unsupportedContext(string $context): string
    {
        return $context . '_unsupported';
    }

    private function handlerWith(RecordingRefreshHandler ...$handlers): CacheRefreshCommandHandler
    {
        return new CacheRefreshCommandHandler(
            new CacheRefreshCommandHandlerResolver($handlers),
            $this->cachePoolResolver,
            $this->logger,
            $this->metricsEmitter
        );
    }

    private function expectDedupeClaim(
        CacheRefreshCommand $command,
        bool $releaseOnFailure = false
    ): void {
        $item = $this->createMock(ItemInterface::class);
        $item
            ->expects($this->once())
            ->method('expiresAfter')
            ->with(60);
        $this->cachePoolResolver
            ->expects($this->exactly($releaseOnFailure ? 2 : 1))
            ->method('resolve')
            ->with($command->context())
            ->willReturn($this->dedupeCache);
        $this->dedupeCache
            ->expects($this->once())
            ->method('get')
            ->with($this->dedupeCacheKey($command), $this->isType('callable'))
            ->willReturnCallback(
                static function (string $key, callable $callback) use ($item): bool {
                    return $callback($item);
                }
            );

        if (! $releaseOnFailure) {
            return;
        }

        $this->dedupeCache
            ->expects($this->once())
            ->method('delete')
            ->with($this->dedupeCacheKey($command))
            ->willReturn(true);
    }

    private function expectDedupeHit(CacheRefreshCommand $command): void
    {
        $this->cachePoolResolver
            ->expects($this->once())
            ->method('resolve')
            ->with($command->context())
            ->willReturn($this->dedupeCache);
        $this->dedupeCache
            ->expects($this->once())
            ->method('get')
            ->with($this->dedupeCacheKey($command), $this->isType('callable'))
            ->willReturn(true);
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Duplicate cache refresh command skipped',
                $this->callback(static function (array $context) use ($command): bool {
                    return $context['operation'] === 'cache.refresh.duplicate'
                        && $context['context'] === $command->context()
                        && $context['family'] === $command->family()
                        && $context['dedupe_key'] === $command->dedupeKey();
                })
            );
    }

    private function expectDedupeClaimWithReleaseFailure(CacheRefreshCommand $command): void
    {
        $item = $this->createMock(ItemInterface::class);
        $item
            ->expects($this->once())
            ->method('expiresAfter')
            ->with(60);
        $this->cachePoolResolver
            ->expects($this->exactly(2))
            ->method('resolve')
            ->with($command->context())
            ->willReturn($this->dedupeCache);
        $this->dedupeCache
            ->expects($this->once())
            ->method('get')
            ->willReturnCallback(
                static function (string $key, callable $callback) use ($item): bool {
                    return $callback($item);
                }
            );
        $this->dedupeCache
            ->expects($this->once())
            ->method('delete')
            ->with($this->dedupeCacheKey($command))
            ->willThrowException(new \RuntimeException('delete failed'));
    }

    private function expectDedupeUnavailableLog(CacheRefreshCommand $command): void
    {
        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'Cache refresh dedupe marker unavailable',
                $this->callback(static function (array $context) use ($command): bool {
                    return $context['operation'] === 'cache.refresh.dedupe_unavailable'
                        && $context['context'] === $command->context()
                        && $context['family'] === $command->family()
                        && $context['dedupe_key'] === $command->dedupeKey()
                        && $context['error'] === 'cache unavailable'
                        && $context['exception'] instanceof \RuntimeException;
                })
            );
    }

    private function expectDedupeReleaseAndRefreshFailureLogs(
        CacheRefreshCommand $command
    ): void {
        $this->logger
            ->expects($this->exactly(2))
            ->method('warning')
            ->willReturnCallback(
                static function (string $message, array $context) use ($command): void {
                    static $call = 0;

                    if ($call === 0) {
                        ++$call;
                        self::assertSame('Cache refresh dedupe marker release failed', $message);
                        self::assertSame(
                            'cache.refresh.dedupe_release_failed',
                            $context['operation']
                        );
                        self::assertSame($command->context(), $context['context']);
                        self::assertSame($command->family(), $context['family']);
                        self::assertSame($command->dedupeKey(), $context['dedupe_key']);
                        self::assertSame('delete failed', $context['error']);
                        self::assertInstanceOf(\RuntimeException::class, $context['exception']);

                        return;
                    }

                    ++$call;
                    self::assertSame('Cache refresh command failed', $message);
                    self::assertTrue(self::failureContextMatches($context, $command));
                }
            );
    }

    private function dedupeCacheKey(CacheRefreshCommand $command): string
    {
        return 'cache_refresh.dedupe.' . $command->dedupeKey();
    }

    private function expectFailureLog(CacheRefreshCommand $command): void
    {
        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'Cache refresh command failed',
                $this->callback(
                    static fn (array $context): bool => self::failureContextMatches(
                        $context,
                        $command
                    )
                )
            );
    }

    /**
     * @param array<string, string|null> $context
     */
    private static function failureContextMatches(
        array $context,
        CacheRefreshCommand $command
    ): bool {
        return $context['operation'] === 'cache.refresh.error'
            && $context['context'] === $command->context()
            && $context['family'] === $command->family()
            && $context['dedupe_key'] === $command->dedupeKey()
            && $context['error'] === 'refresh failed';
    }

    private function assertRefreshFailureIsRethrown(
        CacheRefreshCommandHandler $handler,
        CacheRefreshCommand $command
    ): void {
        try {
            $handler($command);
            self::fail('Expected cache refresh failure to be rethrown.');
        } catch (\RuntimeException $exception) {
            self::assertSame('refresh failed', $exception->getMessage());
        }
    }

    private function assertFailedMetricWasEmitted(
        string $context,
        string $family,
        string $source
    ): void {
        self::assertSame(1, $this->metricsEmitter->count());
        self::assertInstanceOf(
            CacheRefreshFailedMetric::class,
            $this->metricsEmitter->emitted()->all()[0]
        );
        $this->metricsEmitter->assertEmittedWithDimensions(
            'CacheRefreshFailed',
            new MetricDimension('Context', $context),
            new MetricDimension('Family', $family),
            new MetricDimension('Source', $source),
            new MetricDimension('Result', 'failed')
        );
    }
}
