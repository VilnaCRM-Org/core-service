<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\EventDispatcher\Stub;

use App\Shared\Infrastructure\EventDispatcher\ResilientEventSubscriber;

/**
 * Concrete implementation for testing purposes
 */
final readonly class ConcreteResilientEventSubscriber extends ResilientEventSubscriber
{
    public function executeSafely(callable $handler): void
    {
        $this->safeExecute($handler, 'test.event');
    }
}
