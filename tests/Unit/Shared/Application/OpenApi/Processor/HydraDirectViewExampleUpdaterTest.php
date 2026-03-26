<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use App\Shared\Application\OpenApi\Processor\HydraDirectViewExampleUpdater;
use App\Tests\Unit\UnitTestCase;

final class HydraDirectViewExampleUpdaterTest extends UnitTestCase
{
    public function testUpdateRemovesLegacyTypeWhenAtTypeAlreadyExists(): void
    {
        $updater = new HydraDirectViewExampleUpdater();

        $updated = $updater->update([
            'properties' => [
                'view' => [
                    'example' => [
                        '@id' => '/api/customers?page=1',
                        '@type' => 'PartialCollectionView',
                        'type' => 'PartialCollectionView',
                    ],
                ],
            ],
        ]);

        self::assertSame(
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
}
