<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use App\Shared\Application\OpenApi\Processor\CustomerUlidRefReplacer;
use App\Tests\Unit\UnitTestCase;

final class CustomerUlidRefReplacerTest extends UnitTestCase
{
    public function testRewritesUlidRefWhenReferenceIsSupported(): void
    {
        $schemas = $this->createSchemasWithRef('#/components/schemas/UlidInterface');

        $result = (new CustomerUlidRefReplacer())->replace($schemas, 'Customer.jsonld-output');

        self::assertSame($this->createSchemasWithTypeString(), $result);
    }

    public function testRewritesUlidRefWhenReferenceIncludesJsonldOutput(): void
    {
        $schemas = $this->createSchemasWithRef('#/components/schemas/UlidInterface.jsonld-output');

        $result = (new CustomerUlidRefReplacer())->replace($schemas, 'Customer.jsonld-output');

        self::assertNotSame($schemas, $result);
        self::assertSame(['type' => 'string'], $result['Customer.jsonld-output']['properties']['ulid']);
    }

    public function testDoesNotRewriteUlidRefWhenReferenceHasPrefix(): void
    {
        $schemas = $this->createSchemasWithRef('foo#/components/schemas/UlidInterface');

        $result = (new CustomerUlidRefReplacer())->replace($schemas, 'Customer.jsonld-output');

        self::assertSame($schemas, $result);
    }

    public function testDoesNotRewriteUlidRefWhenReferenceHasSuffix(): void
    {
        $schemas = $this->createSchemasWithRef('#/components/schemas/UlidInterface.jsonld-output-extra');

        $result = (new CustomerUlidRefReplacer())->replace($schemas, 'Customer.jsonld-output');

        self::assertSame($schemas, $result);
    }

    public function testRewritesUlidRefInsideAllOfFragment(): void
    {
        $schemas = [
            'CustomerType.jsonld-output' => [
                'allOf' => [
                    [
                        'type' => 'object',
                        'properties' => [
                            'value' => ['type' => 'string'],
                            'ulid' => [
                                '$ref' => '#/components/schemas/UlidInterface.jsonld-output',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $result = (new CustomerUlidRefReplacer())->replace($schemas, 'CustomerType.jsonld-output');

        self::assertSame(
            ['type' => 'string'],
            $result['CustomerType.jsonld-output']['allOf'][0]['properties']['ulid']
        );
    }

    public function testDoesNotRewriteUnsupportedUlidRefInsideAllOfFragment(): void
    {
        $schemas = [
            'CustomerType.jsonld-output' => [
                'allOf' => [
                    [
                        'type' => 'object',
                        'properties' => [
                            'ulid' => [
                                '$ref' => '#/components/schemas/SomeOtherSchema',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $result = (new CustomerUlidRefReplacer())->replace($schemas, 'CustomerType.jsonld-output');

        self::assertSame($schemas, $result);
    }

    public function testDoesNotRewriteSingleSparseAllOfWhenFragmentIsUnchanged(): void
    {
        $schemas = [
            'CustomerType.jsonld-output' => [
                'allOf' => [
                    5 => [
                        'type' => 'object',
                        'properties' => [
                            'ulid' => [
                                '$ref' => '#/components/schemas/SomeOtherSchema',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $result = (new CustomerUlidRefReplacer())->replace($schemas, 'CustomerType.jsonld-output');

        self::assertSame($schemas, $result);
        self::assertSame([5], array_keys($result['CustomerType.jsonld-output']['allOf']));
    }

    public function testDoesNotRewriteSparseAllOfWhenNoFragmentsChange(): void
    {
        $schemas = [
            'CustomerType.jsonld-output' => [
                'allOf' => [
                    2 => [
                        'type' => 'object',
                        'properties' => [
                            'ulid' => [
                                '$ref' => '#/components/schemas/SomeOtherSchema',
                            ],
                        ],
                    ],
                    5 => [
                        'type' => 'object',
                        'properties' => [
                            'value' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
        ];

        $result = (new CustomerUlidRefReplacer())->replace($schemas, 'CustomerType.jsonld-output');

        self::assertSame($schemas, $result);
        self::assertSame([2, 5], array_keys($result['CustomerType.jsonld-output']['allOf']));
    }

    public function testReindexesSparseAllOfWhenFragmentIsUpdated(): void
    {
        $schemas = [
            'CustomerType.jsonld-output' => [
                'allOf' => [
                    2 => [
                        'type' => 'object',
                        'properties' => [
                            'value' => ['type' => 'string'],
                        ],
                    ],
                    5 => [
                        'type' => 'object',
                        'properties' => [
                            'ulid' => [
                                '$ref' => '#/components/schemas/UlidInterface.jsonld-output',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $result = (new CustomerUlidRefReplacer())->replace($schemas, 'CustomerType.jsonld-output');

        self::assertSame([0, 1], array_keys($result['CustomerType.jsonld-output']['allOf']));
        self::assertSame(
            ['type' => 'string'],
            $result['CustomerType.jsonld-output']['allOf'][1]['properties']['ulid']
        );
    }

    /**
     * @return array<string, array<string, array<string, array<string, string>>>>
     */
    private function createSchemasWithRef(string $ref): array
    {
        return [
            'Customer.jsonld-output' => [
                'type' => 'object',
                'properties' => [
                    'ulid' => [
                        '$ref' => $ref,
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<string, array<string, array<string, string>>>>
     */
    private function createSchemasWithTypeString(): array
    {
        return [
            'Customer.jsonld-output' => [
                'type' => 'object',
                'properties' => [
                    'ulid' => [
                        'type' => 'string',
                    ],
                ],
            ],
        ];
    }
}
