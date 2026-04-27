<?php

declare(strict_types=1);

namespace App\Shared\Application\CommandHandler;

use App\Shared\Application\Command\CacheRefreshCommand;
use App\Shared\Application\DTO\CacheRefreshPolicy;
use App\Shared\Application\DTO\CacheRefreshResult;

abstract class CacheRefreshCommandHandlerBase
{
    final public function __invoke(CacheRefreshCommand $command): CacheRefreshResult
    {
        if ($command->context() !== $this->context()) {
            return $this->skipped($command, 'unsupported_context');
        }

        if ($command->refreshSource() === CacheRefreshPolicy::SOURCE_INVALIDATE_ONLY) {
            return $this->skipped($command, CacheRefreshPolicy::SOURCE_INVALIDATE_ONLY);
        }

        return $this->refresh($command);
    }

    abstract public function context(): string;

    abstract protected function refresh(CacheRefreshCommand $command): CacheRefreshResult;

    private function skipped(CacheRefreshCommand $command, string $reason): CacheRefreshResult
    {
        return CacheRefreshResult::skipped(
            $command->context(),
            $command->family(),
            $command->dedupeKey(),
            $reason
        );
    }
}
