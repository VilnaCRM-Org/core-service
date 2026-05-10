<?php

declare(strict_types=1);

namespace App\Shared\Application\Factory;

use App\Shared\Application\Command\CacheRefreshCommand;
use App\Shared\Application\DTO\CacheRefreshResult;

final readonly class CacheRefreshResultFactory
{
    public function success(CacheRefreshCommand $command): CacheRefreshResult
    {
        return $this->create($command, true, 'refreshed');
    }

    public function skipped(CacheRefreshCommand $command, string $reason): CacheRefreshResult
    {
        return $this->create($command, false, $reason);
    }

    private function create(
        CacheRefreshCommand $command,
        bool $wasRefreshed,
        string $reason
    ): CacheRefreshResult {
        return new CacheRefreshResult(
            $command->context(),
            $command->family(),
            $command->dedupeKey(),
            $wasRefreshed,
            $reason
        );
    }
}
