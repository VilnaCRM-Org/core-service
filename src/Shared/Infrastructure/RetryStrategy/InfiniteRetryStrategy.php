<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\RetryStrategy;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Retry\RetryStrategyInterface;

/**
 * Infinite retry strategy for failed domain events (self-healing pipeline)
 *
 * Failed messages are retried indefinitely with a fixed delay.
 * This supports our AP (Availability + Partition tolerance) architecture:
 * - Events that fail are never lost
 * - They remain in the failed queue until the issue is resolved
 * - Metrics + alerts trigger automated fixes via AI agents
 */
final readonly class InfiniteRetryStrategy implements RetryStrategyInterface
{
    private const RETRY_DELAY_MS = 60000; // 60 seconds

    public function isRetryable(Envelope $message, ?\Throwable $throwable = null): bool
    {
        return true;
    }

    public function getWaitingTime(Envelope $message, ?\Throwable $throwable = null): int
    {
        return self::RETRY_DELAY_MS;
    }
}
