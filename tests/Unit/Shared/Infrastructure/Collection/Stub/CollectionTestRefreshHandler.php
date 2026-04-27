<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Collection\Stub;

use App\Shared\Application\Command\CacheRefreshCommand;
use App\Shared\Application\CommandHandler\CacheRefreshCommandHandlerBase;
use App\Shared\Application\DTO\CacheRefreshResult;

final class CollectionTestRefreshHandler extends CacheRefreshCommandHandlerBase
{
    public function context(): string
    {
        return 'customer';
    }

    protected function refresh(CacheRefreshCommand $command): CacheRefreshResult
    {
        return CacheRefreshResult::success(
            $command->context(),
            $command->family(),
            $command->dedupeKey()
        );
    }
}
