<?php

declare(strict_types=1);

namespace App\Shared\Domain\Bus\Event;

/** @psalm-suppress UnusedClass */
interface EventBus
{
    public function publish(DomainEvent ...$events): void;
}
