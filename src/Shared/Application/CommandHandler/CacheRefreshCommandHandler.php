<?php

declare(strict_types=1);

namespace App\Shared\Application\CommandHandler;

use App\Shared\Application\Command\CacheRefreshCommand;
use App\Shared\Application\Observability\Emitter\BusinessMetricsEmitterInterface;
use App\Shared\Application\Observability\Metric\CacheRefreshFailedMetric;
use App\Shared\Application\Observability\Metric\CacheRefreshSucceededMetric;
use App\Shared\Application\Resolver\CachePoolResolverInterface;
use App\Shared\Application\Resolver\CacheRefreshCommandHandlerResolverInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Throwable;

/**
 * @psalm-suppress UnusedClass Wired as a Messenger handler in config/services.yaml.
 */
final readonly class CacheRefreshCommandHandler
{
    private const DEDUPE_TTL_SECONDS = 60;
    private const DEDUPE_KEY_PREFIX = 'cache_refresh.dedupe.';

    public function __construct(
        private CacheRefreshCommandHandlerResolverInterface $resolver,
        private CachePoolResolverInterface $cachePoolResolver,
        private LoggerInterface $logger,
        private BusinessMetricsEmitterInterface $metricsEmitter
    ) {
    }

    public function __invoke(CacheRefreshCommand $command): void
    {
        if (! $this->claimDedupeMarker($command)) {
            return;
        }

        try {
            $this->refreshCache($command);
        } catch (Throwable $e) {
            $this->releaseDedupeMarker($command);
            $this->handleRefreshFailure($command, $e);

            throw $e;
        }
    }

    private function refreshCache(CacheRefreshCommand $command): void
    {
        $result = $this->resolver->resolve($command->context())->__invoke($command);

        if (! $result->refreshed()) {
            return;
        }

        $this->emitRefreshSucceeded($command);
    }

    private function emitRefreshSucceeded(CacheRefreshCommand $command): void
    {
        $this->metricsEmitter->emit(CacheRefreshSucceededMetric::create(
            $command->context(),
            $command->family(),
            $command->refreshSource()
        ));
    }

    private function handleRefreshFailure(
        CacheRefreshCommand $command,
        Throwable $e
    ): void {
        $this->logRefreshFailure($command, $e);
        $this->emitRefreshFailed($command);
    }

    private function logRefreshFailure(
        CacheRefreshCommand $command,
        Throwable $e
    ): void {
        $this->logger->warning('Cache refresh command failed', [
            'operation' => 'cache.refresh.error',
            'context' => $command->context(),
            'family' => $command->family(),
            'dedupe_key' => $command->dedupeKey(),
            'error' => $e->getMessage(),
        ]);
    }

    private function emitRefreshFailed(CacheRefreshCommand $command): void
    {
        $this->metricsEmitter->emit(CacheRefreshFailedMetric::create(
            $command->context(),
            $command->family(),
            $command->refreshSource()
        ));
    }

    private function claimDedupeMarker(CacheRefreshCommand $command): bool
    {
        try {
            if ($this->tryClaimDedupeMarker($command)) {
                return true;
            }

            $this->logDuplicateRefresh($command);

            return false;
        } catch (Throwable $e) {
            $this->logDedupeMarkerUnavailable($command, $e);

            return true;
        }
    }

    private function tryClaimDedupeMarker(CacheRefreshCommand $command): bool
    {
        $claimed = false;
        $cache = $this->cachePoolResolver->resolve($command->context());
        $cache->get(
            $this->dedupeCacheKey($command),
            $this->dedupeMarkerLoader($claimed)
        );

        return $claimed;
    }

    /**
     * @return callable(ItemInterface): bool
     */
    private function dedupeMarkerLoader(bool &$claimed): callable
    {
        return static function (ItemInterface $item) use (&$claimed): bool {
            $claimed = true;
            $item->expiresAfter(self::DEDUPE_TTL_SECONDS);

            return $claimed;
        };
    }

    private function logDuplicateRefresh(CacheRefreshCommand $command): void
    {
        $this->logger->info('Duplicate cache refresh command skipped', [
            'operation' => 'cache.refresh.duplicate',
            'context' => $command->context(),
            'family' => $command->family(),
            'dedupe_key' => $command->dedupeKey(),
        ]);
    }

    private function logDedupeMarkerUnavailable(
        CacheRefreshCommand $command,
        Throwable $e
    ): void {
        $this->logger->warning('Cache refresh dedupe marker unavailable', [
            'operation' => 'cache.refresh.dedupe_unavailable',
            'context' => $command->context(),
            'family' => $command->family(),
            'dedupe_key' => $command->dedupeKey(),
            'error' => $e->getMessage(),
            'exception' => $e,
        ]);
    }

    private function releaseDedupeMarker(CacheRefreshCommand $command): void
    {
        try {
            $this->cachePoolResolver
                ->resolve($command->context())
                ->delete($this->dedupeCacheKey($command));
        } catch (Throwable $e) {
            $this->logger->warning('Cache refresh dedupe marker release failed', [
                'operation' => 'cache.refresh.dedupe_release_failed',
                'context' => $command->context(),
                'family' => $command->family(),
                'dedupe_key' => $command->dedupeKey(),
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);
        }
    }

    private function dedupeCacheKey(CacheRefreshCommand $command): string
    {
        return self::DEDUPE_KEY_PREFIX . $command->dedupeKey();
    }
}
