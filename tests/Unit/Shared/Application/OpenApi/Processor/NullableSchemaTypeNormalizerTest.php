<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use App\Shared\Application\OpenApi\Processor\NullableSchemaTypeNormalizer;
use App\Tests\Unit\UnitTestCase;

final class NullableSchemaTypeNormalizerTest extends UnitTestCase
{
    public function testNormalizeReturnsNullWhenTypeIsNotNullable(): void
    {
        $normalizer = new NullableSchemaTypeNormalizer();

        self::assertNull($normalizer->normalize([
            'type' => 'string',
        ]));
    }

    public function testNormalizeReturnsSingleNonNullableTypeWhenPossible(): void
    {
        $normalizer = new NullableSchemaTypeNormalizer();

        self::assertSame(
            ['type' => 'string'],
            $normalizer->normalize([
                'type' => ['string', 'null'],
            ])
        );
    }

    public function testNormalizeReturnsNullForAmbiguousNullableTypes(): void
    {
        $normalizer = new NullableSchemaTypeNormalizer();

        self::assertNull($normalizer->normalize([
            'type' => ['string', 'integer', 'null'],
        ]));
    }
}
