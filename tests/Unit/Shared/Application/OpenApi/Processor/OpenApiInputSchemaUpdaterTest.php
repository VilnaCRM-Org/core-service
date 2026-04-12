<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Components;
use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Processor\NullableSchemaTypeNormalizer;
use App\Shared\Application\OpenApi\Processor\OpenApiInputSchemaUpdater;
use App\Shared\Application\OpenApi\Processor\RequiredSchemaPropertyUpdater;
use App\Shared\Application\OpenApi\Processor\SchemaNormalizer;
use App\Tests\Unit\UnitTestCase;
use ArrayObject;

final class OpenApiInputSchemaUpdaterTest extends UnitTestCase
{
    public function testUpdateMakesRequiredInputPropertiesNonNullable(): void
    {
        $openApi = new OpenApi(
            new Info('VilnaCRM', '1.0.0', 'Spec under test'),
            [],
            new Paths(),
            new Components(
                new ArrayObject([
                    'Customer.CustomerCreate' => [
                        'required' => ['confirmed'],
                        'properties' => [
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
            'boolean',
            SchemaNormalizer::normalize(
                SchemaNormalizer::normalize(
                    SchemaNormalizer::normalize($schemas['Customer.CustomerCreate'])['properties']
                )['confirmed']
            )['type']
        );
        self::assertSame(
            'string',
            SchemaNormalizer::normalize(
                SchemaNormalizer::normalize(
                    SchemaNormalizer::normalize($schemas['CustomerType.TypeCreate'])['properties']
                )['value']
            )['type']
        );
    }

    public function testUpdateLeavesNonRequiredAndAlreadyNormalizedPropertiesUntouched(): void
    {
        $schemas = new ArrayObject([
            'Customer.CustomerCreate' => [
                'required' => ['initials'],
                'properties' => [
                    'confirmed' => ['type' => ['boolean', 'null']],
                ],
            ],
            'CustomerType.TypeCreate' => [
                'required' => ['value'],
                'properties' => [
                    'value' => ['type' => 'string'],
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

    public function testUpdateContinuesAfterUnchangedSchemaEntries(): void
    {
        $openApi = new OpenApi(
            new Info('VilnaCRM', '1.0.0', 'Spec under test'),
            [],
            new Paths(),
            new Components(
                new ArrayObject([
                    'Customer.CustomerCreate' => [
                        'required' => ['initials'],
                        'properties' => [
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
            ['boolean', 'null'],
            SchemaNormalizer::normalize(
                SchemaNormalizer::normalize(
                    SchemaNormalizer::normalize($schemas['Customer.CustomerCreate'])['properties']
                )['confirmed']
            )['type']
        );
        self::assertSame(
            'string',
            SchemaNormalizer::normalize(
                SchemaNormalizer::normalize(
                    SchemaNormalizer::normalize($schemas['CustomerType.TypeCreate'])['properties']
                )['value']
            )['type']
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
}
