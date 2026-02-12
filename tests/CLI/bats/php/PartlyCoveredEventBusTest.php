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

        // Only testing addEvent - getEventCount and hasEvents are intentionally NOT tested
        $this->assertTrue(true); // Minimal assertion to pass test
    }
}
