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

    public function testUpdateReturnsNullWhenAllOfIsExplicitlyNull(): void
    {
        $allOfUpdater = $this->createMock(HydraAllOfUpdater::class);
        $allOfUpdater->expects($this->never())
            ->method('update');

        $updater = new HydraViewExampleUpdater($allOfUpdater);

        $this->assertNull($updater->update(['allOf' => null]));
    }

    public function testUpdateReturnsNullWhenAllOfUpdateReturnsNull(): void
    {
        $normalized = ['allOf' => [['type' => 'object']]];
        $allOfUpdater = $this->createMock(HydraAllOfUpdater::class);
        $allOfUpdater->expects($this->once())
            ->method('update')
            ->with([['type' => 'object']])
            ->willReturn(null);

        $updater = new HydraViewExampleUpdater($allOfUpdater);

        $this->assertNull($updater->update($normalized));
    }

    public function testUpdateReturnsUpdatedAllOfPayload(): void
    {
        $normalized = ['allOf' => [['type' => 'object']]];
        $updatedAllOf = [['type' => 'string']];
        $allOfUpdater = $this->createMock(HydraAllOfUpdater::class);
        $allOfUpdater->expects($this->once())
            ->method('update')
            ->with([['type' => 'object']])
            ->willReturn($updatedAllOf);

        $updater = new HydraViewExampleUpdater($allOfUpdater);

        $this->assertSame(['allOf' => $updatedAllOf], $updater->update($normalized));
    }
}
