<?php

declare(strict_types=1);

namespace App\Tests\Support\Memory;

use PHPUnit\Framework\TestCase;
use ShipMonk\MemoryScanner\ObjectDeallocationChecker;

final class ObjectDeallocationWatcher
{
    private ?ObjectDeallocationChecker $objectDeallocationChecker = null;

    public function expect(object $object, string $label): void
    {
        $this->objectDeallocationChecker ??= new ObjectDeallocationChecker();
        $this->objectDeallocationChecker->expectDeallocation($object, $label);
    }

    public function assertAllReleased(TestCase $testCase): void
    {
        if ($this->objectDeallocationChecker === null) {
            return;
        }

        $deallocationChecker = $this->objectDeallocationChecker;
        $this->objectDeallocationChecker = null;

        $leakCauses = $deallocationChecker->checkDeallocations();

        $testCase::assertSame([], $leakCauses, $deallocationChecker->explainLeaks($leakCauses));
    }
}
