<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use App\Shared\Application\OpenApi\Processor\HydraAllOfItemUpdater;
use App\Shared\Application\OpenApi\Processor\HydraAllOfUpdater;
use App\Tests\Unit\UnitTestCase;
use ArrayObject;

final class HydraAllOfUpdaterTest extends UnitTestCase
{
    public function testUpdateReturnsNullWhenNoArrayItems(): void
    {
        $itemUpdater = $this->createMock(HydraAllOfItemUpdater::class);
        $itemUpdater->expects($this->never())
            ->method('update');

        $updater = new HydraAllOfUpdater($itemUpdater);

        $this->assertNull($updater->update(['not-an-array']));
    }

    public function testUpdateReturnsNullWhenNoItemsChanged(): void
    {
        $itemUpdater = $this->createMock(HydraAllOfItemUpdater::class);
        $itemUpdater->expects($this->exactly(2))
            ->method('update')
            ->willReturn(null);

        $updater = new HydraAllOfUpdater($itemUpdater);

        $allOf = [
            ['type' => 'object'],
            ['type' => 'string'],
        ];

        $this->assertNull($updater->update($allOf));
    }

    public function testUpdateUpdatesAllMatchingItems(): void
    {
        $itemUpdater = $this->createMock(HydraAllOfItemUpdater::class);
        $itemUpdater->expects($this->exactly(2))
            ->method('update')
            ->willReturnCallback(static fn ($item) => new ArrayObject(array_merge(
                (array) $item,
                ['updated' => true]
            )));

        $updater = new HydraAllOfUpdater($itemUpdater);

        $allOf = [
            ['type' => 'object'],
            ['type' => 'string'],
        ];

        $result = $updater->update($allOf);

        $this->assertNotNull($result);
        $this->assertTrue(($result[0]['updated'] ?? false));
        $this->assertTrue(($result[1]['updated'] ?? false));
    }
}
