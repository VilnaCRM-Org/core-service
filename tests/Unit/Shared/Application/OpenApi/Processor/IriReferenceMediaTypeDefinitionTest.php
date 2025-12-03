<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use App\Shared\Application\OpenApi\ValueObject\IriReferenceMediaTypeDefinition;
use App\Tests\Unit\UnitTestCase;

final class IriReferenceMediaTypeDefinitionTest extends UnitTestCase
{
    public function testTransformWithReturnsNullWhenTransformerMakesNoChanges(): void
    {
        $definition = IriReferenceMediaTypeDefinition::from($this->createMediaType());
        self::assertNotNull($definition);

        $result = $definition->transformWith(static fn (array $property): array => $property);

        self::assertNull($result);
    }

    public function testTransformWithReturnsUpdatedPropertiesWhenTransformerChangesData(): void
    {
        $definition = IriReferenceMediaTypeDefinition::from($this->createMediaType());
        self::assertNotNull($definition);

        $result = $definition->transformWith(static fn (array $property): array => [
            ...$property,
            'deprecated' => true,
        ]);

        self::assertNotNull($result);
        self::assertArrayHasKey('deprecated', $result['id']);
        self::assertTrue($result['id']['deprecated']);
    }

    public function testWithPropertiesReplacesSchemaProperties(): void
    {
        $definition = IriReferenceMediaTypeDefinition::from($this->createMediaType());
        self::assertNotNull($definition);

        $newProperties = [
            'id' => ['type' => 'string'],
            'newField' => ['type' => 'integer'],
        ];

        $mediaType = $definition->withProperties($newProperties);

        self::assertSame($newProperties, $mediaType['schema']['properties']);
    }

    /** @return array<string, array<string, array<string, string>>> */
    private function createMediaType(): array
    {
        return [
            'schema' => [
                'properties' => [
                    'id' => ['type' => 'string'],
                ],
            ],
        ];
    }
}
