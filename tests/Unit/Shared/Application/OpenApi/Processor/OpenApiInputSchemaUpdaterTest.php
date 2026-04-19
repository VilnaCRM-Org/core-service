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
}
