<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use App\Shared\Application\OpenApi\Processor\ConstraintViolationPayloadItemsCleaner;
use App\Tests\Unit\UnitTestCase;

final class ConstraintViolationPayloadItemsCleanerTest extends UnitTestCase
{
    public function testCleanRemovesNullItemsFromStringArrayPayload(): void
    {
        $cleaned = ConstraintViolationPayloadItemsCleaner::clean([
            'type' => 'array',
            'items' => null,
        ]);

        self::assertSame(['type' => 'array'], $cleaned);
    }
}
