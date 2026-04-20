<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\EventDispatcher;

use App\Shared\Infrastructure\EventDispatcher\SafeQueryKeyValidator;
use App\Tests\Unit\UnitTestCase;

final class SafeQueryKeyValidatorTest extends UnitTestCase
{
    public function testRejectsEmptyKeys(): void
    {
        $validator = new SafeQueryKeyValidator();

        self::assertFalse($validator->isSafe(''));
    }

    public function testRejectsMalformedUtf8Keys(): void
    {
        $validator = new SafeQueryKeyValidator();

        self::assertFalse($validator->isSafe('a%F1%87%8E%80%F3%86%9B%8F%5B'));
    }

    public function testRejectsUnbalancedBracketKeys(): void
    {
        $validator = new SafeQueryKeyValidator();

        self::assertFalse($validator->isSafe('broken%5B'));
    }

    public function testAcceptsNestedArraySyntax(): void
    {
        $validator = new SafeQueryKeyValidator();

        self::assertTrue($validator->isSafe('filters%5B%5D%5Bvalue%5D'));
    }
}
