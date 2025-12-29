<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\RetryStrategy;

use App\Shared\Infrastructure\RetryStrategy\InfiniteRetryStrategy;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Messenger\Envelope;

final class InfiniteRetryStrategyTest extends UnitTestCase
{
    private InfiniteRetryStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->strategy = new InfiniteRetryStrategy();
    }

    public function testIsRetryableAlwaysReturnsTrue(): void
    {
        $envelope = new Envelope(new \stdClass());

        self::assertTrue($this->strategy->isRetryable($envelope));
    }

    public function testIsRetryableReturnsTrueEvenWithException(): void
    {
        $envelope = new Envelope(new \stdClass());
        $exception = new \RuntimeException('Test exception');

        self::assertTrue($this->strategy->isRetryable($envelope, $exception));
    }

    public function testGetWaitingTimeReturns60Seconds(): void
    {
        $envelope = new Envelope(new \stdClass());

        self::assertSame(60000, $this->strategy->getWaitingTime($envelope));
    }

    public function testGetWaitingTimeReturns60SecondsEvenWithException(): void
    {
        $envelope = new Envelope(new \stdClass());
        $exception = new \RuntimeException('Test exception');

        self::assertSame(60000, $this->strategy->getWaitingTime($envelope, $exception));
    }
}
