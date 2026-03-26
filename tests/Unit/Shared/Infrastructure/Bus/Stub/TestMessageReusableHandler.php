<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Stub;

/**
 * @psalm-suppress UnusedClass
 */
final class TestMessageReusableHandler
{
    public function __construct(private \stdClass $counter)
    {
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __invoke(TestMessage $_message): void
    {
        ++$this->counter->value;
    }
}
