<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use App\Shared\Application\OpenApi\Processor\HydraAtTypeExampleUpdater;
use App\Tests\Unit\UnitTestCase;

final class HydraAtTypeExampleUpdaterTest extends UnitTestCase
{
    public function testUpdateRemovesLegacyTypeWhenHydraTypeAlreadyExists(): void
    {
        $updater = new HydraAtTypeExampleUpdater();

        self::assertSame(
            [
                '@type' => 'PartialCollectionView',
                '@id' => '/api/customers?page=1',
            ],
            $updater->update([
                'type' => 'PartialCollectionView',
                '@type' => 'PartialCollectionView',
                '@id' => '/api/customers?page=1',
            ])
        );
    }
}
