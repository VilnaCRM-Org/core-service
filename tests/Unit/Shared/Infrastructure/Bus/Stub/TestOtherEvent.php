<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Stub;

use App\Shared\Domain\Bus\Event\DomainEvent;

final class TestOtherEvent extends DomainEvent
{
    public function eventName(): string
    {
        return 'test.other_event';
    }

    /**
     * @return array<string, string>
     */
    public function toPrimitives(): array
    {
        return [];
    }
}
