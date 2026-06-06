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
            (new SchemaNormalizer())->normalize($schemas['Customer.CustomerPut'])['required']
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
                    'id' => ['type' => ['string', 'null']],
                    'initials' => ['type' => ['string', 'null']],
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
            1,
            (new SchemaNormalizer())->normalize($updatedSchemas['Customer.CustomerPatch.jsonMergePatch'])['minProperties']
        );
        self::assertArrayNotHasKey(
            'id',
            (new SchemaNormalizer())->normalize($updatedSchemas['Customer.CustomerPatch.jsonMergePatch'])['properties']
        );
        self::assertSame(
            1,
            $this->schemaProperty($updatedSchemas, 'Customer.CustomerPatch.jsonMergePatch', 'initials')['minLength']
        );
        self::assertSame(
            'iri-reference',
            $this->schemaProperty($updatedSchemas, 'Customer.CustomerPatch.jsonMergePatch', 'type')['format']
        );
    }

    public function testUpdateRequiresActionableSingleFieldPatchSchemas(): void
    {
        $schemas = new ArrayObject([
            'CustomerStatus.StatusPatch.jsonMergePatch' => [
                'properties' => [
                    'value' => ['type' => ['string', 'null']],
                ],
            ],
            'CustomerType.TypePatch.jsonMergePatch' => [
                'properties' => [
                    'value' => ['type' => ['string', 'null']],
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
        foreach ([
            'CustomerStatus.StatusPatch.jsonMergePatch',
            'CustomerType.TypePatch.jsonMergePatch',
        ] as $schemaName) {
            self::assertSame(
                ['value'],
                (new SchemaNormalizer())->normalize($updatedSchemas[$schemaName])['required']
            );
            self::assertSame(
                'string',
                $this->schemaProperty($updatedSchemas, $schemaName, 'value')['type']
            );
            self::assertSame(
                1,
                $this->schemaProperty($updatedSchemas, $schemaName, 'value')['minLength']
            );
        }
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
            (new SchemaNormalizer())->normalize($result[0]['properties'])['confirmed']['type']
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

    public function testUpdatedSchemaKeywordsDoesNotReportChangesForNoOpSchemaPatch(): void
    {
        $schema = ['minProperties' => 1];

        $result = $this->invokeUpdaterMethod('updatedSchemaKeywords', [
            'Customer.CustomerPatch.jsonMergePatch',
            $schema,
        ]);

        self::assertSame($schema, $result[0]);
        self::assertFalse($result[1]);
    }

    public function testUpdatedSchemaKeywordsReportsChangedSchemaPatch(): void
    {
        $result = $this->invokeUpdaterMethod('updatedSchemaKeywords', [
            'Customer.CustomerPatch.jsonMergePatch',
            ['properties' => []],
        ]);

        self::assertSame(1, $result[0]['minProperties']);
        self::assertTrue($result[1]);
    }

    public function testUpdatedSchemaReturnsSchemaWhenOnlySchemaKeywordsChange(): void
    {
        $result = $this->invokeUpdaterMethod('updatedSchema', [
            'Customer.CustomerPatch.jsonMergePatch',
            [
                'properties' => [
                    'initials' => ['type' => 'string'],
                ],
            ],
            [],
        ]);

        self::assertIsArray($result);
        self::assertSame(1, $result['minProperties']);
    }

    public function testUpdatedSchemaReturnsSchemaWhenOnlyRequiredPropertiesChange(): void
    {
        $result = $this->invokeUpdaterMethod('updatedSchema', [
            'Customer.CustomerPut',
            [
                'required' => ['initials'],
                'properties' => [
                    'confirmed' => ['type' => 'boolean'],
                ],
            ],
            [],
        ]);

        self::assertIsArray($result);
        self::assertSame(['initials', 'confirmed'], $result['required']);
    }

    public function testUpdatedSchemaReturnsSchemaWhenOnlyConfiguredPropertiesAreRemoved(): void
    {
        $result = $this->invokeUpdaterMethod('updatedSchema', [
            'Customer.CustomerPatch.jsonMergePatch',
            [
                'minProperties' => 1,
                'properties' => [
                    'id' => ['type' => 'string'],
                    'initials' => ['type' => 'string'],
                ],
            ],
            [],
        ]);

        self::assertIsArray($result);
        self::assertArrayNotHasKey(
            'id',
            (new SchemaNormalizer())->normalize($result['properties'])
        );
    }

    public function testUpdatedSchemaReturnsSchemaWhenOnlyPropertySchemaChanges(): void
    {
        $result = $this->invokeUpdaterMethod('updatedSchema', [
            'CustomerType.TypeCreate',
            [
                'required' => ['value'],
                'properties' => [
                    'value' => ['type' => 'string'],
                ],
            ],
            ['value' => ['minLength' => 1]],
        ]);

        self::assertIsArray($result);
        self::assertSame(1, $result['properties']['value']['minLength']);
    }

    public function testUpdatedPropertiesAfterRemovalDoesNotReportChangesWhenPropertyIsAbsent(): void
    {
        $schema = [
            'properties' => [
                'initials' => ['type' => ['string', 'null']],
            ],
        ];

        $result = $this->invokeUpdaterMethod('updatedPropertiesAfterRemoval', [
            'Customer.CustomerPatch.jsonMergePatch',
            $schema,
        ]);

        self::assertSame($schema, $result[0]);
        self::assertFalse($result[1]);
    }

    public function testUpdatedPropertiesAfterRemovalReportsChangedSchema(): void
    {
        $result = $this->invokeUpdaterMethod('updatedPropertiesAfterRemoval', [
            'Customer.CustomerPatch.jsonMergePatch',
            [
                'properties' => [
                    'id' => ['type' => 'string'],
                    'initials' => ['type' => 'string'],
                ],
            ],
        ]);

        self::assertArrayNotHasKey(
            'id',
            (new SchemaNormalizer())->normalize($result[0]['properties'])
        );
        self::assertTrue($result[1]);
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
        return (new SchemaNormalizer())->normalize(
            (new SchemaNormalizer())->normalize(
                (new SchemaNormalizer())->normalize($schemas[$schemaName])['properties']
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
