<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\Emf;

/**
 * System-based timestamp provider using microtime
 */
final readonly class SystemEmfTimestampProvider implements EmfTimestampProvider
{
    public function currentTimestamp(): int
    {
        return (int) (microtime(true) * 1000);
    }
}
