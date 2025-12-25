<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\EventDispatcher\Stub;

use App\Shared\Infrastructure\EventDispatcher\ResilientEventSubscriber;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Concrete implementation for testing purposes
 */
final readonly class ConcreteResilientEventSubscriber extends ResilientEventSubscriber
{
    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onResponse',
        ];
    }

    public function executeSafely(callable $handler): void
    {
        $this->safeExecute($handler, 'test.event');
    }
}
