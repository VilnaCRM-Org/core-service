<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use App\Shared\Application\OpenApi\Processor\HydraAllOfItemUpdater;
use App\Shared\Application\OpenApi\Processor\HydraAllOfUpdater;
use App\Tests\Unit\UnitTestCase;

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
}
