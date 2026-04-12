<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\RequestBody;
use App\Shared\Application\OpenApi\Processor\CustomerTypeRequestBodyPathUpdater;
use App\Shared\Application\OpenApi\Processor\RequestBodyContentSchemaRefUpdater;
use App\Shared\Application\OpenApi\Processor\RequestBodySchemaRefDefinitionUpdater;
use App\Shared\Application\OpenApi\Processor\RequestBodySchemaRefUpdater;
use App\Tests\Unit\UnitTestCase;
use ArrayObject;

final class CustomerTypeRequestBodyPathUpdaterTest extends UnitTestCase
{
    public function testUpdateReplacesInlineRequestBodySchemasWithComponentRefs(): void
    {
        $pathItem = (new PathItem())->withPost(
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
                            'example' => ['value' => 'Prospect'],
                        ],
                    ]),
                    required: true
                )
            )
        );

        $updated = $this->createUpdater()->update($pathItem);
        $content = $updated->getPost()?->getRequestBody()?->getContent();

        self::assertInstanceOf(ArrayObject::class, $content);
        self::assertSame(
            ['$ref' => '#/components/schemas/CustomerType.TypeCreate'],
            $content['application/ld+json']['schema']
        );
        self::assertSame(
            ['value' => 'Prospect'],
            $content['application/ld+json']['example']
        );
    }

    public function testUpdateLeavesNonPostAndAlreadyNormalizedDefinitionsUntouched(): void
    {
        $getOnly = (new PathItem())->withGet(new Operation());
        self::assertSame($getOnly, $this->createUpdater()->update($getOnly));

        $pathItem = (new PathItem())->withPost(
            new Operation(
                requestBody: new RequestBody(
                    content: new ArrayObject([
                        'application/ld+json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/CustomerType.TypeCreate',
                            ],
                        ],
                    ])
                )
            )
        );

        self::assertSame($pathItem, $this->createUpdater()->update($pathItem));
    }

    public function testUpdateLeavesOperationsWithoutRenderableRequestBodiesUntouched(): void
    {
        $withoutRequestBody = (new PathItem())->withPost(new Operation());
        self::assertSame(
            $withoutRequestBody,
            $this->createUpdater()->update($withoutRequestBody)
        );

        $withoutContent = (new PathItem())->withPost(
            new Operation(requestBody: new RequestBody())
        );
        self::assertSame(
            $withoutContent,
            $this->createUpdater()->update($withoutContent)
        );
    }

    public function testUpdateSkipsNonArrayContentDefinitions(): void
    {
        $pathItem = (new PathItem())->withPost(
            new Operation(
                requestBody: new RequestBody(
                    content: new ArrayObject([
                        'application/ld+json' => new ArrayObject(['schema' => []]),
                    ])
                )
            )
        );

        self::assertSame($pathItem, $this->createUpdater()->update($pathItem));
    }

    public function testUpdateContinuesPastNonArrayContentDefinitions(): void
    {
        $pathItem = (new PathItem())->withPost(
            new Operation(
                requestBody: new RequestBody(
                    content: new ArrayObject([
                        'application/problem+json' => new ArrayObject(['schema' => []]),
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
        );

        $updated = $this->createUpdater()->update($pathItem);
        $content = $updated->getPost()?->getRequestBody()?->getContent();

        self::assertInstanceOf(ArrayObject::class, $content);
        self::assertSame(
            ['$ref' => '#/components/schemas/CustomerType.TypeCreate'],
            $content['application/ld+json']['schema']
        );
        self::assertInstanceOf(ArrayObject::class, $content['application/problem+json']);
    }

    public function testUpdateContinuesPastAlreadyNormalizedContentDefinitions(): void
    {
        $pathItem = (new PathItem())->withPost(
            new Operation(
                requestBody: new RequestBody(
                    content: new ArrayObject([
                        'application/problem+json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/CustomerType.TypeCreate',
                            ],
                        ],
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
        );

        $updated = $this->createUpdater()->update($pathItem);
        $content = $updated->getPost()?->getRequestBody()?->getContent();

        self::assertInstanceOf(ArrayObject::class, $content);
        self::assertSame(
            ['$ref' => '#/components/schemas/CustomerType.TypeCreate'],
            $content['application/problem+json']['schema']
        );
        self::assertSame(
            ['$ref' => '#/components/schemas/CustomerType.TypeCreate'],
            $content['application/ld+json']['schema']
        );
    }

    private function createUpdater(): CustomerTypeRequestBodyPathUpdater
    {
        return new CustomerTypeRequestBodyPathUpdater(
            new RequestBodySchemaRefUpdater(
                new RequestBodyContentSchemaRefUpdater(
                    new RequestBodySchemaRefDefinitionUpdater()
                )
            )
        );
    }
}
