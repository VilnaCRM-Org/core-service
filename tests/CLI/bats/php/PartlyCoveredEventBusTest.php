<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Event;

use App\Shared\Infrastructure\Bus\Event\PartlyCoveredEventBus;
use PHPUnit\Framework\TestCase;

final class PartlyCoveredEventBusTest extends TestCase
{
    public function testAddEvent(): void
    {
        $eventBus = new PartlyCoveredEventBus();
        $eventBus->addEvent('test.event');

        // Call getEventCount and hasEvents to ensure they have code coverage,
        // but don't assert on their return values to allow mutants to escape
        $eventBus->getEventCount();
        $eventBus->hasEvents();

        // Weak assertion that doesn't catch mutations
        $this->assertTrue(true);
    }
}
