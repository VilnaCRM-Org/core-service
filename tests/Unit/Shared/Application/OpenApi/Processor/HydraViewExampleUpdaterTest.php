<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use App\Shared\Application\OpenApi\Processor\HydraAllOfUpdater;
use App\Shared\Application\OpenApi\Processor\HydraViewExampleUpdater;
use App\Shared\Application\OpenApi\Updater\HydraDirectViewExampleUpdater;
use App\Tests\Unit\UnitTestCase;

final class HydraViewExampleUpdaterTest extends UnitTestCase
{
    public function testUpdateReturnsNullWhenAllOfAndDirectViewAreMissing(): void
    {
        $allOfUpdater = $this->createMock(HydraAllOfUpdater::class);
        $allOfUpdater->expects($this->never())
            ->method('update');

        $updater = new HydraViewExampleUpdater(
            $allOfUpdater,
            new HydraDirectViewExampleUpdater()
        );

        $this->assertNull($updater->update(['properties' => []]));
    }

    public function testUpdateReturnsUpdatedDirectViewExample(): void
    {
        $allOfUpdater = $this->createMock(HydraAllOfUpdater::class);
        $allOfUpdater->expects($this->never())
            ->method('update');

        $updater = new HydraViewExampleUpdater(
            $allOfUpdater,
            new HydraDirectViewExampleUpdater()
        );

        $updated = $updater->update([
            'properties' => [
                'view' => [
                    'example' => [
                        '@id' => '/api/customers?page=1',
                        'type' => 'PartialCollectionView',
                    ],
                ],
            ],
        ]);

        $this->assertSame(
            [
                'properties' => [
                    'view' => [
                        'example' => [
                            '@id' => '/api/customers?page=1',
                            '@type' => 'PartialCollectionView',
                        ],
                    ],
                ],
            ],
            $updated
        );
    }

    public function testUpdateAppliesDirectViewExampleAndAllOfUpdatesWhenBothExist(): void
    {
        $updatedAllOf = [['type' => 'string']];
        $allOfUpdater = $this->createMock(HydraAllOfUpdater::class);
        $allOfUpdater->expects($this->once())
            ->method('update')
            ->with([['type' => 'object']])
            ->willReturn($updatedAllOf);

        $updater = new HydraViewExampleUpdater(
            $allOfUpdater,
            new HydraDirectViewExampleUpdater()
        );

        $normalized = [
            'properties' => [
                'view' => [
                    'example' => [
                        '@id' => '/api/customers?page=1',
                        'type' => 'PartialCollectionView',
                    ],
                ],
            ],
            'allOf' => [
                ['type' => 'object'],
            ],
        ];

        $this->assertSame(
            [
                'properties' => [
                    'view' => [
                        'example' => [
                            '@id' => '/api/customers?page=1',
                            '@type' => 'PartialCollectionView',
                        ],
                    ],
                ],
                'allOf' => $updatedAllOf,
            ],
            $updater->update($normalized)
        );
    }

    public function testUpdateReturnsNullWhenAllOfIsExplicitlyNull(): void
    {
        $allOfUpdater = $this->createMock(HydraAllOfUpdater::class);
        $allOfUpdater->expects($this->never())
            ->method('update');

        $updater = new HydraViewExampleUpdater(
            $allOfUpdater,
            new HydraDirectViewExampleUpdater()
        );

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

        $updater = new HydraViewExampleUpdater(
            $allOfUpdater,
            new HydraDirectViewExampleUpdater()
        );

        $this->assertNull($updater->update($normalized));
    }

    public function testUpdateReturnsUpdatedAllOfPayload(): void
    {
        $normalized = [
            'title' => 'Hydra collection',
            'allOf' => [['type' => 'object']],
        ];
        $updatedAllOf = [['type' => 'string']];
        $allOfUpdater = $this->createMock(HydraAllOfUpdater::class);
        $allOfUpdater->expects($this->once())
            ->method('update')
            ->with([['type' => 'object']])
            ->willReturn($updatedAllOf);

        $updater = new HydraViewExampleUpdater(
            $allOfUpdater,
            new HydraDirectViewExampleUpdater()
        );

        $this->assertSame(
            [
                'title' => 'Hydra collection',
                'allOf' => $updatedAllOf,
            ],
            $updater->update($normalized)
        );
    }
}
