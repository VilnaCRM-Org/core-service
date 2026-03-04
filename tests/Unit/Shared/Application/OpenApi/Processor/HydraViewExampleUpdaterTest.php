<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use App\Shared\Application\OpenApi\Processor\HydraAllOfUpdater;
use App\Shared\Application\OpenApi\Processor\HydraViewExampleUpdater;
use App\Tests\Unit\UnitTestCase;

final class HydraViewExampleUpdaterTest extends UnitTestCase
{
    public function testUpdateReturnsNullWhenAllOfIsMissing(): void
    {
        $allOfUpdater = $this->createMock(HydraAllOfUpdater::class);
        $allOfUpdater->expects($this->never())
            ->method('update');

        $updater = new HydraViewExampleUpdater($allOfUpdater);

        $this->assertNull($updater->update(['properties' => []]));
    }
}
