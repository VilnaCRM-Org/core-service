<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use App\Shared\Application\OpenApi\Processor\ConstraintViolationPayloadItemsCleaner;
use App\Tests\Unit\UnitTestCase;
use ArrayObject;

final class ConstraintViolationPayloadItemsCleanerTest extends UnitTestCase
{
    public function testCleanRemovesNullItemsFromStringArrayPayload(): void
    {
        $cleaned = (new ConstraintViolationPayloadItemsCleaner())->clean([
            'type' => 'array',
            'items' => null,
        ]);

        self::assertSame(['type' => 'array'], $cleaned);
    }

    public function testCleanRemovesNullItemsFromArrayObjectArrayPayload(): void
    {
        $type = new ArrayObject(['array']);
        $cleaned = (new ConstraintViolationPayloadItemsCleaner())->clean([
            'type' => $type,
            'items' => null,
        ]);

        self::assertSame(['type' => $type], $cleaned);
    }
}
