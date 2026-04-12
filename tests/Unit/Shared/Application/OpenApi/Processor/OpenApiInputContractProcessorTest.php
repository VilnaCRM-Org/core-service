<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Components;
use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Server;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Processor\CustomerTypeRequestBodyPathUpdater;
use App\Shared\Application\OpenApi\Processor\NullableSchemaTypeNormalizer;
use App\Shared\Application\OpenApi\Processor\OpenApiInputContractProcessor;
use App\Shared\Application\OpenApi\Processor\OpenApiInputSchemaUpdater;
use App\Shared\Application\OpenApi\Processor\RequestBodyContentSchemaRefUpdater;
use App\Shared\Application\OpenApi\Processor\RequestBodySchemaRefDefinitionUpdater;
use App\Shared\Application\OpenApi\Processor\RequestBodySchemaRefUpdater;
use App\Shared\Application\OpenApi\Processor\RequiredSchemaPropertyUpdater;
use App\Shared\Application\OpenApi\Processor\SchemaNormalizer;
use App\Tests\Unit\UnitTestCase;
use ArrayObject;

final class OpenApiInputContractProcessorTest extends UnitTestCase
{
    public function testProcessAppliesInputFixesOnlyToCustomerTypeCollectionPath(): void
    {
        $paths = new Paths();
        $paths->addPath(
            '/api/customer_types',
            (new PathItem())->withPost(
                new Operation(
                    requestBody: new RequestBody(
                        content: new ArrayObject([
                            'application/ld+json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'value' => ['type' => 'string'],
                                    ],
                                ],
                            ],
                        ])
                    )
                )
            )
        );
        $paths->addPath(
            '/api/customer_statuses',
            (new PathItem())->withPost(
                new Operation(
                    requestBody: new RequestBody(
                        content: new ArrayObject([
                            'application/ld+json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'value' => ['type' => 'string'],
                                    ],
                                ],
                            ],
                        ])
                    )
                )
            )
        );

        $openApi = new OpenApi(
            new Info('VilnaCRM', '1.0.0', 'Spec under test'),
            [new Server('https://localhost')],
            $paths,
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
                            'value' => ['type' => 'string'],
                        ],
                    ],
                ])
            )
        );

        $processed = $this->createProcessor()->process($openApi);
        $schemas = $processed->getComponents()->getSchemas();
        $customerTypeContent = $processed->getPaths()
            ->getPath('/api/customer_types')
            ->getPost()
            ?->getRequestBody()
            ?->getContent();
        $customerStatusContent = $processed->getPaths()
            ->getPath('/api/customer_statuses')
            ->getPost()
            ?->getRequestBody()
            ?->getContent();

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
        self::assertInstanceOf(ArrayObject::class, $customerTypeContent);
        self::assertSame(
            ['$ref' => '#/components/schemas/CustomerType.TypeCreate'],
            $customerTypeContent['application/ld+json']['schema']
        );
        self::assertInstanceOf(ArrayObject::class, $customerStatusContent);
        self::assertSame(
            'object',
            $customerStatusContent['application/ld+json']['schema']['type']
        );
    }

    private function createProcessor(): OpenApiInputContractProcessor
    {
        return new OpenApiInputContractProcessor(
            new OpenApiInputSchemaUpdater(
                new RequiredSchemaPropertyUpdater(
                    new NullableSchemaTypeNormalizer()
                )
            ),
            new CustomerTypeRequestBodyPathUpdater(
                new RequestBodySchemaRefUpdater(
                    new RequestBodyContentSchemaRefUpdater(
                        new RequestBodySchemaRefDefinitionUpdater()
                    )
                )
            )
        );
    }
}
