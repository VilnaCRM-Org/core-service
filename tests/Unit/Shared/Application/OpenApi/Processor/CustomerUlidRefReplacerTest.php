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
