<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Collection\Stub;

use App\Shared\Application\Command\CacheRefreshCommand;
use App\Shared\Application\CommandHandler\CacheRefreshCommandHandlerBase;
use App\Shared\Application\DTO\CacheRefreshResult;
use App\Shared\Application\Factory\CacheRefreshResultFactory;

final class CollectionTestRefreshHandler extends CacheRefreshCommandHandlerBase
{
    public function __construct()
    {
        parent::__construct(new CacheRefreshResultFactory());
    }

    public function context(): string
    {
        return 'customer';
    }

    protected function refresh(CacheRefreshCommand $command): CacheRefreshResult
    {
        return $this->succeeded($command);
    }
}
