<?php

declare(strict_types=1);

namespace App\Tests\Support\Memory;

use PHPUnit\Framework\TestCase;
use ShipMonk\MemoryScanner\ObjectDeallocationChecker;

final class ObjectDeallocationWatcher
{
    private ?ObjectDeallocationChecker $objectDeallocationChecker = null;

    /**
     * @var array<string, int>
     */
    private array $labelUsageCounts = [];

    public function expect(object $object, string $label): void
    {
        $this->objectDeallocationChecker ??= new ObjectDeallocationChecker();
        $this->objectDeallocationChecker->expectDeallocation($object, $this->reserveUniqueLabel($label));
    }

    public function assertAllReleased(TestCase $testCase): void
    {
        if ($this->objectDeallocationChecker === null) {
            return;
        }

        $deallocationChecker = $this->objectDeallocationChecker;
        $this->objectDeallocationChecker = null;
        $this->labelUsageCounts = [];

        $leakCauses = $deallocationChecker->checkDeallocations();

        $testCase::assertSame([], $leakCauses, $deallocationChecker->explainLeaks($leakCauses));
    }

    private function reserveUniqueLabel(string $label): string
    {
        $usageCount = $this->labelUsageCounts[$label] ?? 0;
        $this->labelUsageCounts[$label] = $usageCount + 1;

        if ($usageCount === 0) {
            return $label;
        }

        return sprintf('%s #%d', $label, $usageCount + 1);
    }
}
