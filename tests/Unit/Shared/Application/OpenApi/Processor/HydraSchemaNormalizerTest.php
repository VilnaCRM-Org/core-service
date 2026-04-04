<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use App\Shared\Application\OpenApi\Serializer\HydraSchemaNormalizer;
use App\Tests\Unit\UnitTestCase;

final class HydraSchemaNormalizerTest extends UnitTestCase
{
    public function testNormalizeAddsAllOfKeyWhenMissing(): void
    {
        $normalizer = new HydraSchemaNormalizer();
        $schemas = [
            'HydraCollectionBaseSchema' => ['some' => 'value'],
            'UnrelatedSchema' => ['type' => 'string'],
        ];

        $result = $normalizer->normalize($schemas);

        self::assertSame(
            [
                'HydraCollectionBaseSchema' => [
                    'some' => 'value',
                    'allOf' => [],
                ],
                'UnrelatedSchema' => ['type' => 'string'],
            ],
            $result
        );
    }

    public function testNormalizeKeepsExistingAllOfKey(): void
    {
        $normalizer = new HydraSchemaNormalizer();
        $schemas = [
            'HydraCollectionBaseSchema' => [
                'allOf' => [
                    ['existing' => 'value'],
                ],
            ],
            'UnrelatedSchema' => ['type' => 'integer'],
        ];

        $result = $normalizer->normalize($schemas);

        self::assertSame(
            [
                'HydraCollectionBaseSchema' => [
                    'allOf' => [
                        ['existing' => 'value'],
                    ],
                ],
                'UnrelatedSchema' => ['type' => 'integer'],
            ],
            $result
        );
    }

    public function testNormalizeWithNullSchema(): void
    {
        $normalizer = new HydraSchemaNormalizer();
        $schemas = [
            'HydraCollectionBaseSchema' => null,
            'UnrelatedSchema' => ['type' => 'boolean'],
        ];

        $result = $normalizer->normalize($schemas);

        self::assertSame(
            [
                'HydraCollectionBaseSchema' => [
                    'allOf' => [],
                ],
                'UnrelatedSchema' => ['type' => 'boolean'],
            ],
            $result
        );
    }

    public function testNormalizeReplacesNullAllOfWithEmptyArray(): void
    {
        $normalizer = new HydraSchemaNormalizer();
        $schemas = [
            'HydraCollectionBaseSchema' => [
                'allOf' => null,
                'type' => 'object',
            ],
        ];

        self::assertSame(
            [
                'HydraCollectionBaseSchema' => [
                    'allOf' => [],
                    'type' => 'object',
                ],
            ],
            $normalizer->normalize($schemas)
        );
    }
}
