<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\EventDispatcher;

use App\Shared\Infrastructure\EventDispatcher\QueryStringSanitizer;
use App\Shared\Infrastructure\EventDispatcher\SafeQueryKeyValidator;
use App\Tests\Unit\UnitTestCase;

final class QueryStringSanitizerTest extends UnitTestCase
{
    public function testSkipsNamelessAndEmptyQueryParts(): void
    {
        $sanitizer = new QueryStringSanitizer(new SafeQueryKeyValidator());

        self::assertSame(
            'itemsPerPage=5',
            $sanitizer->sanitize('=ignored&&itemsPerPage=5')
        );
    }

    public function testKeepsValuesThatContainEncodedEqualsSigns(): void
    {
        $sanitizer = new QueryStringSanitizer(new SafeQueryKeyValidator());

        self::assertSame(
            'search=vip%3Dgold&itemsPerPage=5',
            $sanitizer->sanitize('search=vip%3Dgold&itemsPerPage=5')
        );
    }
}
