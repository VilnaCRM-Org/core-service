<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use App\Shared\Application\OpenApi\Processor\HydraSchemaNormalizer;
use App\Tests\Unit\UnitTestCase;

final class HydraSchemaNormalizerTest extends UnitTestCase
{
    public function testNormalizeAddsAllOfKeyWhenMissing(): void
    {
        $normalizer = new HydraSchemaNormalizer();
        $schemas = ['HydraCollectionBaseSchema' => ['some' => 'value']];

        $result = $normalizer->normalize($schemas);

        self::assertArrayHasKey('allOf', $result);
        self::assertNull($result['allOf']);
    }

    public function testNormalizeKeepsExistingAllOfKey(): void
    {
        $normalizer = new HydraSchemaNormalizer();
        $schemas = ['HydraCollectionBaseSchema' => ['allOf' => ['existing' => 'value']]];

        $result = $normalizer->normalize($schemas);

        self::assertSame(['existing' => 'value'], $result['allOf']);
    }

    public function testNormalizeWithNullSchema(): void
    {
        $normalizer = new HydraSchemaNormalizer();
        $schemas = ['HydraCollectionBaseSchema' => null];

        $result = $normalizer->normalize($schemas);

        self::assertArrayHasKey('allOf', $result);
        self::assertNull($result['allOf']);
    }
}
