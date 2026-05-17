<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\CommandHandler\Stub;

use App\Shared\Application\Command\CacheRefreshCommand;
use App\Shared\Application\CommandHandler\CacheRefreshCommandHandlerBase;
use App\Shared\Application\DTO\CacheRefreshResult;
use App\Shared\Application\Factory\CacheRefreshResultFactory;

final class RecordingRefreshHandler extends CacheRefreshCommandHandlerBase
{
    private int $calls = 0;
    private ?CacheRefreshCommand $lastCommand = null;

    public function __construct(
        private readonly string $context,
        private readonly bool $refreshed
    ) {
        parent::__construct(new CacheRefreshResultFactory());
    }

    public function context(): string
    {
        return $this->context;
    }

    public function calls(): int
    {
        return $this->calls;
    }

    public function lastCommand(): ?CacheRefreshCommand
    {
        return $this->lastCommand;
    }

    protected function refresh(CacheRefreshCommand $command): CacheRefreshResult
    {
        ++$this->calls;
        $this->lastCommand = $command;

        if (! $this->refreshed) {
            return $this->skipped($command, 'not_needed');
        }

        return $this->succeeded($command);
    }
}
