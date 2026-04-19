<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\RequestBody;
use App\Shared\Application\OpenApi\Processor\RequestBodyContentSchemaRefUpdater;
use App\Shared\Application\OpenApi\Processor\RequestBodyPathUpdater;
use App\Shared\Application\OpenApi\Processor\RequestBodySchemaRefDefinitionUpdater;
use App\Shared\Application\OpenApi\Processor\RequestBodySchemaRefUpdater;
use App\Tests\Unit\UnitTestCase;
use ArrayObject;

final class RequestBodyPathUpdaterTest extends UnitTestCase
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

        $updated = $this->createUpdater()->update($pathItem, [
            'Post' => '#/components/schemas/CustomerType.TypeCreate',
        ]);
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

    public function testUpdateLeavesUnknownOperationsAndNormalizedDefinitionsUntouched(): void
    {
        $getOnly = (new PathItem())->withGet(new Operation());
        self::assertSame($getOnly, $this->createUpdater()->update($getOnly, [
            'Post' => '#/components/schemas/CustomerType.TypeCreate',
        ]));

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

        self::assertSame($pathItem, $this->createUpdater()->update($pathItem, [
            'Post' => '#/components/schemas/CustomerType.TypeCreate',
        ]));
    }

    public function testUpdateLeavesOperationsWithoutRenderableRequestBodiesUntouched(): void
    {
        $withoutRequestBody = (new PathItem())->withPost(new Operation());
        self::assertSame(
            $withoutRequestBody,
            $this->createUpdater()->update($withoutRequestBody, [
                'Post' => '#/components/schemas/CustomerType.TypeCreate',
            ])
        );

        $withoutContent = (new PathItem())->withPost(
            new Operation(requestBody: new RequestBody())
        );
        self::assertSame(
            $withoutContent,
            $this->createUpdater()->update($withoutContent, [
                'Post' => '#/components/schemas/CustomerType.TypeCreate',
            ])
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

        self::assertSame($pathItem, $this->createUpdater()->update($pathItem, [
            'Post' => '#/components/schemas/CustomerType.TypeCreate',
        ]));
    }

    public function testUpdateContinuesPastMixedContentDefinitions(): void
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

        $updated = $this->createUpdater()->update($pathItem, [
            'Post' => '#/components/schemas/CustomerType.TypeCreate',
        ]);
        $content = $updated->getPost()?->getRequestBody()?->getContent();

        self::assertInstanceOf(ArrayObject::class, $content);
        self::assertSame(
            ['$ref' => '#/components/schemas/CustomerType.TypeCreate'],
            $content['application/ld+json']['schema']
        );
        self::assertInstanceOf(ArrayObject::class, $content['application/problem+json']);
    }

    public function testUpdateCanApplyDifferentRefsPerOperation(): void
    {
        $pathItem = (new PathItem())
            ->withPut(
                new Operation(
                    requestBody: new RequestBody(
                        content: new ArrayObject([
                            'application/ld+json' => [
                                'schema' => ['type' => 'object'],
                            ],
                        ])
                    )
                )
            )
            ->withPatch(
                new Operation(
                    requestBody: new RequestBody(
                        content: new ArrayObject([
                            'application/merge-patch+json' => [
                                'schema' => ['type' => 'object'],
                            ],
                        ])
                    )
                )
            );

        $updated = $this->createUpdater()->update($pathItem, [
            'Put' => '#/components/schemas/CustomerType.TypePut',
            'Patch' => '#/components/schemas/CustomerType.TypePatch.jsonMergePatch',
        ]);

        self::assertSame(
            ['$ref' => '#/components/schemas/CustomerType.TypePut'],
            $updated->getPut()?->getRequestBody()?->getContent()['application/ld+json']['schema']
        );
        self::assertSame(
            ['$ref' => '#/components/schemas/CustomerType.TypePatch.jsonMergePatch'],
            $updated->getPatch()?->getRequestBody()?->getContent()['application/merge-patch+json']['schema']
        );
    }

    public function testUpdateContinuesPastOperationsThatDoNotNeedChanges(): void
    {
        $pathItem = (new PathItem())
            ->withPut(
                new Operation(
                    requestBody: new RequestBody(
                        content: new ArrayObject([
                            'application/ld+json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/CustomerType.TypePut',
                                ],
                            ],
                        ])
                    )
                )
            )
            ->withPatch(
                new Operation(
                    requestBody: new RequestBody(
                        content: new ArrayObject([
                            'application/merge-patch+json' => [
                                'schema' => ['type' => 'object'],
                            ],
                        ])
                    )
                )
            );

        $updated = $this->createUpdater()->update($pathItem, [
            'Put' => '#/components/schemas/CustomerType.TypePut',
            'Patch' => '#/components/schemas/CustomerType.TypePatch.jsonMergePatch',
        ]);

        self::assertSame(
            ['$ref' => '#/components/schemas/CustomerType.TypePut'],
            $updated->getPut()?->getRequestBody()?->getContent()['application/ld+json']['schema']
        );
        self::assertSame(
            ['$ref' => '#/components/schemas/CustomerType.TypePatch.jsonMergePatch'],
            $updated->getPatch()?->getRequestBody()?->getContent()['application/merge-patch+json']['schema']
        );
    }

    public function testUpdateContinuesPastUnknownOperationsBeforeApplyingKnownOnes(): void
    {
        $pathItem = (new PathItem())->withPatch(
            new Operation(
                requestBody: new RequestBody(
                    content: new ArrayObject([
                        'application/merge-patch+json' => [
                            'schema' => ['type' => 'object'],
                        ],
                    ])
                )
            )
        );

        $updated = $this->createUpdater()->update($pathItem, [
            'Post' => '#/components/schemas/CustomerType.TypeCreate',
            'Patch' => '#/components/schemas/CustomerType.TypePatch.jsonMergePatch',
        ]);

        self::assertSame(
            ['$ref' => '#/components/schemas/CustomerType.TypePatch.jsonMergePatch'],
            $updated->getPatch()?->getRequestBody()?->getContent()['application/merge-patch+json']['schema']
        );
    }

    private function createUpdater(): RequestBodyPathUpdater
    {
        return new RequestBodyPathUpdater(
            new RequestBodySchemaRefUpdater(
                new RequestBodyContentSchemaRefUpdater(
                    new RequestBodySchemaRefDefinitionUpdater()
                )
            )
        );
    }
}
