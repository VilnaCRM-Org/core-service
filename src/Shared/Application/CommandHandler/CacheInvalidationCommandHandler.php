<?php

declare(strict_types=1);

namespace App\Shared\Application\CommandHandler;

use App\Shared\Application\Command\CacheInvalidationCommand;
use App\Shared\Application\Command\CacheRefreshCommand;
use App\Shared\Application\DTO\CacheRefreshPolicy;
use App\Shared\Application\Observability\Emitter\BusinessMetricsEmitterInterface;
use App\Shared\Application\Observability\Metric\CacheRefreshScheduledMetric;
use App\Shared\Application\Resolver\CachePoolResolverInterface;
use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;

final readonly class CacheInvalidationCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private CachePoolResolverInterface $cachePoolResolver,
        private MessageBusInterface $messageBus,
        private LoggerInterface $logger,
        private BusinessMetricsEmitterInterface $metricsEmitter
    ) {
    }

    public function __invoke(CacheInvalidationCommand $command): void
    {
        $this->tryHandle($command);
    }

    public function tryHandle(CacheInvalidationCommand $command): bool
    {
        $invalidated = $this->invalidateTags($command);
        $this->dispatchRefreshCommands($command);

        return $invalidated;
    }

    private function invalidateTags(CacheInvalidationCommand $command): bool
    {
        if ($command->tags()->isEmpty()) {
            return true;
        }

        try {
            /** @var list<string> $tags */
            $tags = iterator_to_array($command->tags());
            $cache = $this->cachePoolResolver->resolve($command->context());

            if ($cache->invalidateTags($tags) === true) {
                return true;
            }

            $this->logInvalidationReturnedFalse($command);
        } catch (Throwable $e) {
            $this->logInvalidationFailed($command, $e);
        }

        return false;
    }

    private function dispatchRefreshCommands(CacheInvalidationCommand $command): void
    {
        foreach ($command->refreshCommands() as $refreshCommand) {
            if (! $this->shouldDispatchRefresh($refreshCommand)) {
                continue;
            }

            $this->dispatchRefreshCommand($refreshCommand);
        }
    }

    private function logInvalidationReturnedFalse(CacheInvalidationCommand $command): void
    {
        $this->logger->warning('Cache invalidation returned false', [
            'operation' => 'cache.invalidation.error',
            'context' => $command->context(),
            'source' => $command->source(),
            'dedupe_key' => $command->dedupeKey(),
        ]);
    }

    private function logInvalidationFailed(
        CacheInvalidationCommand $command,
        Throwable $e
    ): void {
        $this->logger->warning('Cache invalidation failed', [
            'operation' => 'cache.invalidation.error',
            'context' => $command->context(),
            'source' => $command->source(),
            'dedupe_key' => $command->dedupeKey(),
            'error' => $e->getMessage(),
            'exception' => $e,
        ]);
    }

    private function shouldDispatchRefresh(CacheRefreshCommand $refreshCommand): bool
    {
        return $refreshCommand->refreshSource() !== CacheRefreshPolicy::SOURCE_INVALIDATE_ONLY;
    }

    private function dispatchRefreshCommand(CacheRefreshCommand $refreshCommand): void
    {
        try {
            $this->messageBus->dispatch($refreshCommand);
            $this->emitRefreshScheduled($refreshCommand);
        } catch (Throwable $e) {
            $this->logRefreshSchedulingFailed($refreshCommand, $e);
        }
    }

    private function emitRefreshScheduled(CacheRefreshCommand $refreshCommand): void
    {
        $this->metricsEmitter->emit(CacheRefreshScheduledMetric::create(
            $refreshCommand->context(),
            $refreshCommand->family(),
            $refreshCommand->refreshSource()
        ));
    }

    private function logRefreshSchedulingFailed(
        CacheRefreshCommand $refreshCommand,
        Throwable $e
    ): void {
        $this->logger->warning('Cache refresh scheduling failed', [
            'operation' => 'cache.refresh.schedule_error',
            'context' => $refreshCommand->context(),
            'family' => $refreshCommand->family(),
            'dedupe_key' => $refreshCommand->dedupeKey(),
            'error' => $e->getMessage(),
            'exception' => $e,
        ]);
    }
}
