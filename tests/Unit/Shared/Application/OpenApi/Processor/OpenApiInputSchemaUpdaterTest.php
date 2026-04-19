<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Components;
use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\Fixture\SchemathesisFixtures;
use App\Shared\Application\OpenApi\Processor\NullableSchemaTypeNormalizer;
use App\Shared\Application\OpenApi\Processor\OpenApiInputSchemaUpdater;
use App\Shared\Application\OpenApi\Processor\RequiredSchemaPropertyUpdater;
use App\Shared\Application\OpenApi\Processor\SchemaNormalizer;
use App\Tests\Unit\UnitTestCase;
use ArrayObject;
use ReflectionMethod;

final class OpenApiInputSchemaUpdaterTest extends UnitTestCase
{
    public function testUpdateNormalizesRequiredCustomerAndTypeInputSchemas(): void
    {
        $openApi = new OpenApi(
            new Info('VilnaCRM', '1.0.0', 'Spec under test'),
            [],
            new Paths(),
            new Components(
                new ArrayObject([
                    'Customer.CustomerCreate' => [
                        'required' => ['initials', 'type', 'confirmed'],
                        'properties' => [
                            'initials' => ['type' => ['string', 'null']],
                            'type' => ['type' => ['string', 'null']],
                            'confirmed' => ['type' => ['boolean', 'null']],
                        ],
                    ],
                    'Customer.CustomerPut' => [
                        'required' => ['initials', 'type'],
                        'properties' => [
                            'initials' => ['type' => ['string', 'null']],
                            'type' => ['type' => ['string', 'null']],
                            'confirmed' => ['type' => ['boolean', 'null']],
                        ],
                    ],
                    'CustomerType.TypeCreate' => [
                        'required' => ['value'],
                        'properties' => [
                            'value' => ['type' => ['string', 'null']],
                        ],
                    ],
                ])
            )
        );

        $updated = $this->createUpdater()->update($openApi);
        $schemas = $updated->getComponents()->getSchemas();

        self::assertInstanceOf(ArrayObject::class, $schemas);
        self::assertSame(
            1,
            $this->schemaProperty($schemas, 'Customer.CustomerCreate', 'initials')['minLength']
        );
        self::assertSame(
            'string',
            $this->schemaProperty($schemas, 'Customer.CustomerCreate', 'type')['type']
        );
        self::assertSame(
            'iri-reference',
            $this->schemaProperty($schemas, 'Customer.CustomerCreate', 'type')['format']
        );
        self::assertSame(
            [
                '/api/customer_types/' . SchemathesisFixtures::CUSTOMER_TYPE_ID,
                '/api/customer_types/' . SchemathesisFixtures::UPDATE_CUSTOMER_TYPE_ID,
            ],
            $this->schemaProperty($schemas, 'Customer.CustomerCreate', 'type')['enum']
        );
        self::assertSame(
            'boolean',
            $this->schemaProperty($schemas, 'Customer.CustomerCreate', 'confirmed')['type']
        );
        self::assertContains(
            'confirmed',
            SchemaNormalizer::normalize($schemas['Customer.CustomerPut'])['required']
        );
        self::assertSame(
            'boolean',
            $this->schemaProperty($schemas, 'Customer.CustomerPut', 'confirmed')['type']
        );
        self::assertSame(
            'string',
            $this->schemaProperty($schemas, 'CustomerType.TypeCreate', 'value')['type']
        );
        self::assertSame(
            1,
            $this->schemaProperty($schemas, 'CustomerType.TypeCreate', 'value')['minLength']
        );
    }

    public function testUpdateAddsIriConstraintsToPatchSchemasWithoutForcingNullability(): void
    {
        $schemas = new ArrayObject([
            'Customer.CustomerPatch.jsonMergePatch' => [
                'properties' => [
                    'type' => ['type' => ['string', 'null']],
                ],
            ],
        ]);
        $openApi = new OpenApi(
            new Info('VilnaCRM', '1.0.0', 'Spec under test'),
            [],
            new Paths(),
            new Components($schemas)
        );

        $updated = $this->createUpdater()->update($openApi);
        $updatedSchemas = $updated->getComponents()->getSchemas();

        self::assertInstanceOf(ArrayObject::class, $updatedSchemas);
        self::assertSame(
            ['string', 'null'],
            $this->schemaProperty($updatedSchemas, 'Customer.CustomerPatch.jsonMergePatch', 'type')['type']
        );
        self::assertSame(
            'iri-reference',
            $this->schemaProperty($updatedSchemas, 'Customer.CustomerPatch.jsonMergePatch', 'type')['format']
        );
    }

    public function testUpdateLeavesSchemasWithoutComponentDefinitionsUntouched(): void
    {
        $openApi = new OpenApi(
            new Info('VilnaCRM', '1.0.0', 'Spec under test'),
            [],
            new Paths(),
            new Components()
        );

        self::assertSame($openApi, $this->createUpdater()->update($openApi));
    }

    public function testUpdateLeavesAmbiguousNullablePropertyTypesUntouched(): void
    {
        $schemas = new ArrayObject([
            'CustomerType.TypeCreate' => [
                'required' => ['value'],
                'properties' => [
                    'value' => ['type' => ['string', 'integer', 'null']],
                ],
            ],
        ]);
        $openApi = new OpenApi(
            new Info('VilnaCRM', '1.0.0', 'Spec under test'),
            [],
            new Paths(),
            new Components($schemas)
        );

        $updated = $this->createUpdater()->update($openApi);

        self::assertSame($schemas, $updated->getComponents()->getSchemas());
    }

    public function testUpdateIgnoresConfiguredSchemasWhenTheirDefinitionsAreEmpty(): void
    {
        $schemas = new ArrayObject([
            'Customer.CustomerCreate' => [],
        ]);
        $openApi = new OpenApi(
            new Info('VilnaCRM', '1.0.0', 'Spec under test'),
            [],
            new Paths(),
            new Components($schemas)
        );

        self::assertSame($openApi, $this->createUpdater()->update($openApi));
    }

    public function testSupportsPropertySchemaPatchAllowsNonStringSpecificKeywords(): void
    {
        $result = $this->invokeUpdaterMethod('supportsPropertySchemaPatch', [
            ['type' => 'integer'],
            ['default' => true],
        ]);

        self::assertTrue($result);
    }

    public function testSupportsPropertySchemaPatchRejectsStringSpecificKeywordsForNonStringProperties(): void
    {
        $result = $this->invokeUpdaterMethod('supportsPropertySchemaPatch', [
            ['type' => 'integer'],
            ['format' => 'iri-reference'],
        ]);

        self::assertFalse($result);
    }

    public function testMergePropertySchemaPatchReturnsNullWhenPropertyIsMissing(): void
    {
        $result = $this->invokeUpdaterMethod('mergePropertySchemaPatch', [
            ['properties' => []],
            'value',
            ['default' => true],
        ]);

        self::assertNull($result);
    }

    public function testUpdatedRequiredSchemaMarksAddedRequiredPropertiesAsChanged(): void
    {
        $result = $this->invokeUpdaterMethod('updatedRequiredSchema', [
            'Customer.CustomerPut',
            [
                'required' => ['initials'],
                'properties' => [
                    'confirmed' => ['type' => 'boolean'],
                ],
            ],
        ]);

        self::assertSame(['initials', 'confirmed'], $result[0]['required']);
        self::assertTrue($result[1]);
    }

    public function testUpdatedPropertySchemaMarksNonNullableUpdatesWhenSchemaPatchIsEmpty(): void
    {
        $result = $this->invokeUpdaterMethod('updatedPropertySchema', [
            [
                'required' => ['confirmed'],
                'properties' => [
                    'confirmed' => ['type' => ['boolean', 'null']],
                ],
            ],
            'confirmed',
            [],
        ]);

        self::assertSame(
            'boolean',
            SchemaNormalizer::normalize($result[0]['properties'])['confirmed']['type']
        );
        self::assertTrue($result[1]);
    }

    public function testUpdatedPropertySchemaDoesNotReportChangesForNoOpSchemaPatches(): void
    {
        $schema = [
            'properties' => [
                'value' => [
                    'type' => 'string',
                    'minLength' => 1,
                ],
            ],
        ];

        $result = $this->invokeUpdaterMethod('updatedPropertySchema', [
            $schema,
            'value',
            ['minLength' => 1],
        ]);

        self::assertSame($schema, $result[0]);
        self::assertFalse($result[1]);
    }

    public function testEnsureRequiredPropertiesReturnsNullWhenNothingNeedsToBeAdded(): void
    {
        $result = $this->invokeUpdaterMethod('ensureRequiredProperties', [
            ['required' => ['initials', 'confirmed']],
            ['confirmed'],
        ]);

        self::assertNull($result);
    }

    public function testEnsureRequiredPropertiesPreservesExistingOrderAndRemovesDuplicates(): void
    {
        $result = $this->invokeUpdaterMethod('ensureRequiredProperties', [
            ['required' => ['initials', 'phone']],
            ['confirmed', 'status'],
        ]);

        self::assertSame(
            ['initials', 'phone', 'confirmed', 'status'],
            $result['required']
        );
        self::assertSame(
            [0, 1, 2, 3],
            array_keys($result['required'])
        );
    }

    public function testEnsureRequiredPropertiesRemovesDuplicatesFromMergedValues(): void
    {
        $result = $this->invokeUpdaterMethod('ensureRequiredProperties', [
            ['required' => ['initials', 'email']],
            ['email', 'confirmed', 'status'],
        ]);

        self::assertSame(
            ['initials', 'email', 'confirmed', 'status'],
            $result['required']
        );
        self::assertSame(
            [0, 1, 2, 3],
            array_keys($result['required'])
        );
    }

    private function createUpdater(): OpenApiInputSchemaUpdater
    {
        return new OpenApiInputSchemaUpdater(
            new RequiredSchemaPropertyUpdater(
                new NullableSchemaTypeNormalizer()
            )
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function schemaProperty(ArrayObject $schemas, string $schemaName, string $propertyName): array
    {
        return SchemaNormalizer::normalize(
            SchemaNormalizer::normalize(
                SchemaNormalizer::normalize($schemas[$schemaName])['properties']
            )[$propertyName]
        );
    }

    /**
     * @param array<int, mixed> $arguments
     */
    private function invokeUpdaterMethod(string $methodName, array $arguments)
    {
        $method = new ReflectionMethod(OpenApiInputSchemaUpdater::class, $methodName);

        return $method->invokeArgs($this->createUpdater(), $arguments);
    }
}
