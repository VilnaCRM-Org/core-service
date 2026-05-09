<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\CommandHandler\Stub;

use App\Shared\Application\Command\CacheRefreshCommand;
use App\Shared\Application\CommandHandler\CacheRefreshCommandHandlerBase;
use App\Shared\Application\DTO\CacheRefreshResult;

final class FailingRefreshHandler extends CacheRefreshCommandHandlerBase
{
    public function __construct(
        private readonly string $context
    ) {
    }

    public function context(): string
    {
        return $this->context;
    }

    protected function refresh(CacheRefreshCommand $command): CacheRefreshResult
    {
        throw new \RuntimeException('refresh failed');
    }
}
