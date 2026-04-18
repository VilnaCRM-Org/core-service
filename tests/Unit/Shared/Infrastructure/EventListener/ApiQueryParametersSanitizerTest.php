<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\EventListener;

use App\Shared\Infrastructure\EventListener\ApiQueryKeyValidator;
use App\Shared\Infrastructure\EventListener\ApiQueryParametersSanitizer;
use App\Tests\Unit\UnitTestCase;

final class ApiQueryParametersSanitizerTest extends UnitTestCase
{
    public function testSanitizeSkipsUnsafeAndTopLevelIntegerKeys(): void
    {
        $sanitizer = new ApiQueryParametersSanitizer(new ApiQueryKeyValidator());

        self::assertSame(
            [
                'safe' => 'value',
                'nested' => [
                    0 => [
                        'allowed' => 'keep',
                    ],
                ],
            ],
            $sanitizer->sanitize(
                [
                    0 => 'ignored',
                    '' => 'ignored',
                    'safe' => 'value',
                    'nested' => [
                        '' => 'drop',
                        0 => ['allowed' => 'keep'],
                    ],
                ]
            )
        );
    }

    public function testSanitizeReturnsEmptyArrayForNonArrayInput(): void
    {
        $sanitizer = new ApiQueryParametersSanitizer(new ApiQueryKeyValidator());

        self::assertSame([], $sanitizer->sanitize('invalid'));
    }
}
