<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\DTO;

use App\Shared\Application\DTO\CacheInvalidationTagSet;
use App\Tests\Unit\UnitTestCase;

final class CacheInvalidationTagSetTest extends UnitTestCase
{
    public function testWithReturnsNewUniqueTagSet(): void
    {
        $tags = CacheInvalidationTagSet::create('customer', 'customer.detail');
        $updated = $tags->with('customer.detail', 'customer.collection');

        self::assertSame(['customer', 'customer.detail'], iterator_to_array($tags));
        self::assertSame(
            ['customer', 'customer.detail', 'customer.collection'],
            iterator_to_array($updated)
        );
        self::assertCount(3, $updated);
        self::assertFalse($updated->isEmpty());
    }
}
