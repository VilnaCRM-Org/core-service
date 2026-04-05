<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Components;
use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Processor\HydraAllOfItemUpdater;
use App\Shared\Application\OpenApi\Processor\HydraAllOfUpdater;
use App\Shared\Application\OpenApi\Processor\HydraAtTypeExampleUpdater;
use App\Shared\Application\OpenApi\Processor\HydraCollectionSchemaFixer;
use App\Shared\Application\OpenApi\Processor\HydraCollectionSchemasUpdater;
use App\Shared\Application\OpenApi\Processor\HydraViewExampleUpdater;
use App\Shared\Application\OpenApi\Processor\OpenApiArrayContentSchemaUpdater;
use App\Shared\Application\OpenApi\Processor\OpenApiContentDefinitionUpdater;
use App\Shared\Application\OpenApi\Processor\OpenApiMediaTypeSchemaFixer;
use App\Shared\Application\OpenApi\Processor\OpenApiOperationSchemaFixer;
use App\Shared\Application\OpenApi\Processor\OpenApiResponseContentUpdater;
use App\Shared\Application\OpenApi\Processor\OpenApiResponseSchemaFixer;
use App\Shared\Application\OpenApi\Processor\OpenApiResponsesUpdater;
use App\Shared\Application\OpenApi\Processor\OpenApiSchemaFixesProcessor;
use App\Shared\Application\OpenApi\Processor\SchemaNormalizer;
use App\Shared\Application\OpenApi\Serializer\HydraSchemaNormalizer;
use App\Shared\Application\OpenApi\Updater\HydraDirectViewExampleUpdater;
use App\Tests\Unit\UnitTestCase;
use ArrayObject;

final class OpenApiSchemaFixesProcessorTest extends UnitTestCase
{
    public function testProcessUpdatesHydraViewExample(): void
    {
        $schemas = new ArrayObject([
            'HydraCollectionBaseSchema' => new ArrayObject([
                'allOf' => [
                    ['type' => 'object'],
                    [
                        'type' => 'object',
                        'properties' => [
                            'view' => [
                                'type' => 'object',
                                'example' => [
                                    '@id' => 'string',
                                    'type' => 'string',
                                    'first' => 'string',
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $openApi = new OpenApi(
            new Info('Test', '1.0.0'),
            [],
            new Paths(),
            new Components($schemas)
        );

        $processor = $this->createProcessor();
        $result = $processor->process($openApi);

        $resultSchemas = $result->getComponents()->getSchemas();
        $this->assertInstanceOf(ArrayObject::class, $resultSchemas);

        $hydraSchema = SchemaNormalizer::normalize(
            $resultSchemas['HydraCollectionBaseSchema']
        );
        $allOf = $hydraSchema['allOf'];
        $viewSchema = SchemaNormalizer::normalize(
            SchemaNormalizer::normalize($allOf[1])['properties']['view']
        );
        $example = $viewSchema['example'];

        $this->assertSame('string', $example['@type']);
        $this->assertArrayNotHasKey('type', $example);
    }

    public function testProcessLeavesSchemasWhenHydraMissing(): void
    {
        $schemas = new ArrayObject();
        $openApi = new OpenApi(
            new Info('Test', '1.0.0'),
            [],
            new Paths(),
            new Components($schemas)
        );

        $processor = $this->createProcessor();
        $result = $processor->process($openApi);

        $resultSchemas = $result->getComponents()->getSchemas();
        $this->assertInstanceOf(ArrayObject::class, $resultSchemas);
        $this->assertCount(0, $resultSchemas);
    }

    public function testProcessCreatesSchemasWhenMissing(): void
    {
        $openApi = new OpenApi(
            new Info('Test', '1.0.0'),
            [],
            new Paths(),
            null
        );
        $this->assertNull($openApi->getComponents()->getSchemas());

        $processor = $this->createProcessor();
        $result = $processor->process($openApi);

        $this->assertNotNull($result->getComponents());
        $this->assertInstanceOf(ArrayObject::class, $result->getComponents()->getSchemas());
    }

    public function testProcessSkipsInvalidHydraExamples(): void
    {
        $schemas = new ArrayObject([
            'HydraCollectionBaseSchema' => [
                'allOf' => [
                    ['properties' => 'invalid'],
                    ['properties' => ['view' => null]],
                    ['properties' => ['view' => ['example' => ['@id' => 'string']]]],
                ],
            ],
        ]);
        $openApi = new OpenApi(
            new Info('Test', '1.0.0'),
            [],
            new Paths(),
            new Components($schemas)
        );

        $processor = $this->createProcessor();
        $result = $processor->process($openApi);

        $resultSchemas = $result->getComponents()->getSchemas();
        $hydraSchema = SchemaNormalizer::normalize(
            $resultSchemas['HydraCollectionBaseSchema']
        );
        $allOf = $hydraSchema['allOf'];
        $viewSchema = SchemaNormalizer::normalize(
            SchemaNormalizer::normalize($allOf[2])['properties']['view']
        );
        $example = $viewSchema['example'];

        $this->assertArrayHasKey('@id', $example);
        $this->assertArrayNotHasKey('@type', $example);
    }

    public function testProcessContinuesPastInvalidHydraEntries(): void
    {
        $schemas = new ArrayObject([
            'HydraCollectionBaseSchema' => new ArrayObject([
                'allOf' => [
                    ['properties' => ['view' => null]],
                    ['properties' => ['view' => ['example' => ['@id' => 'string']]]],
                    ['properties' => ['view' => ['example' => ['@id' => 'string', 'type' => 'Collection']]]],
                ],
            ]),
        ]);

        $openApi = new OpenApi(
            new Info('Test', '1.0.0'),
            [],
            new Paths(),
            new Components($schemas)
        );

        $processor = $this->createProcessor();
        $result = $processor->process($openApi);

        $resultSchemas = $result->getComponents()->getSchemas();
        $hydraSchema = SchemaNormalizer::normalize(
            $resultSchemas['HydraCollectionBaseSchema']
        );
        $allOf = $hydraSchema['allOf'];
        $viewSchema = SchemaNormalizer::normalize(
            SchemaNormalizer::normalize($allOf[2])['properties']['view']
        );
        $example = $viewSchema['example'];

        $this->assertSame('Collection', $example['@type']);
        $this->assertArrayNotHasKey('type', $example);
    }

    public function testProcessRemovesLegacyTypeWhenHydraExampleAlreadyHasAtType(): void
    {
        $schemas = new ArrayObject([
            'HydraCollectionBaseSchema' => new ArrayObject([
                'allOf' => [
                    [
                        'properties' => [
                            'view' => [
                                'example' => [
                                    '@id' => 'string',
                                    '@type' => 'Collection',
                                    'type' => 'Collection',
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);
        $openApi = new OpenApi(
            new Info('Test', '1.0.0'),
            [],
            new Paths(),
            new Components($schemas)
        );

        $processor = $this->createProcessor();
        $result = $processor->process($openApi);

        $resultSchemas = $result->getComponents()->getSchemas();
        $hydraSchema = SchemaNormalizer::normalize(
            $resultSchemas['HydraCollectionBaseSchema']
        );
        $allOf = $hydraSchema['allOf'];
        $viewSchema = SchemaNormalizer::normalize(
            SchemaNormalizer::normalize($allOf[0])['properties']['view']
        );
        $example = $viewSchema['example'];

        $this->assertSame('Collection', $example['@type']);
        $this->assertArrayNotHasKey('type', $example);
    }

    public function testProcessUpdatesNonBaseHydraCollectionSchemas(): void
    {
        $schemas = new ArrayObject([
            'CustomerCollection.jsonld-output' => new ArrayObject([
                'allOf' => [
                    [
                        'properties' => [
                            'view' => [
                                'example' => [
                                    '@id' => 'string',
                                    'type' => 'Collection',
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $openApi = new OpenApi(
            new Info('Test', '1.0.0'),
            [],
            new Paths(),
            new Components($schemas)
        );

        $processor = $this->createProcessor();
        $result = $processor->process($openApi);

        $resultSchemas = $result->getComponents()->getSchemas();
        $hydraSchema = SchemaNormalizer::normalize(
            $resultSchemas['CustomerCollection.jsonld-output']
        );
        $allOf = $hydraSchema['allOf'];
        $viewSchema = SchemaNormalizer::normalize(
            SchemaNormalizer::normalize($allOf[0])['properties']['view']
        );
        $example = $viewSchema['example'];

        $this->assertSame('Collection', $example['@type']);
        $this->assertArrayNotHasKey('type', $example);
    }

    public function testProcessUpdatesHydraViewExampleInPathResponseSchemas(): void
    {
        $paths = new Paths();
        $paths->addPath(
            '/customers',
            (new PathItem())->withGet(
                new Operation(
                    responses: [
                        '200' => new Response(
                            description: 'ok',
                            content: new ArrayObject([
                                'application/ld+json' => new MediaType(
                                    new ArrayObject([
                                        'allOf' => [
                                            ['type' => 'object'],
                                            [
                                                'type' => 'object',
                                                'properties' => [
                                                    'view' => [
                                                        'type' => 'object',
                                                        'example' => [
                                                            '@id' => '/api/customers?page=1',
                                                            'type' => 'PartialCollectionView',
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ])
                                ),
                            ])
                        ),
                    ]
                )
            )
        );

        $openApi = new OpenApi(
            new Info('Test', '1.0.0'),
            [],
            $paths,
            new Components(new ArrayObject())
        );

        $processor = $this->createProcessor();
        $result = $processor->process($openApi);

        $responses = $result->getPaths()->getPath('/customers')->getGet()?->getResponses();
        self::assertIsArray($responses);

        $content = $responses['200']->getContent();
        self::assertInstanceOf(ArrayObject::class, $content);

        self::assertInstanceOf(MediaType::class, $content['application/ld+json']);

        $schema = SchemaNormalizer::normalize($content['application/ld+json']->getSchema());
        $viewSchema = SchemaNormalizer::normalize(
            SchemaNormalizer::normalize($schema['allOf'][1])['properties']['view']
        );

        self::assertSame('PartialCollectionView', $viewSchema['example']['@type']);
        self::assertArrayNotHasKey('type', $viewSchema['example']);
    }

    public function testProcessLeavesResponsesWithoutContentUntouched(): void
    {
        $paths = new Paths();
        $operation = new Operation(responses: ['200' => new Response(description: 'ok')]);
        $paths->addPath(
            '/customers',
            (new PathItem())->withGet($operation)
        );

        $openApi = new OpenApi(new Info('Test', '1.0.0'), [], $paths, new Components(new ArrayObject()));
        $processor = $this->createProcessor();

        $result = $processor->process($openApi);
        self::assertSame($operation, $result->getPaths()->getPath('/customers')->getGet());
    }

    public function testProcessLeavesOperationsWithoutResponsesUntouched(): void
    {
        $paths = new Paths();
        $operation = new Operation();
        $paths->addPath('/customers', (new PathItem())->withGet($operation));

        $openApi = new OpenApi(new Info('Test', '1.0.0'), [], $paths, new Components(new ArrayObject()));
        $processor = $this->createProcessor();

        $result = $processor->process($openApi);

        self::assertSame($operation, $result->getPaths()->getPath('/customers')->getGet());
    }

    public function testProcessLeavesArrayContentDefinitionsWithoutSchemaUntouched(): void
    {
        $paths = new Paths();
        $response = new Response(
            description: 'ok',
            content: new ArrayObject([
                'application/json' => ['example' => ['type' => 'plain']],
            ])
        );
        $paths->addPath(
            '/customers',
            (new PathItem())->withGet(new Operation(responses: ['200' => $response]))
        );

        $openApi = new OpenApi(new Info('Test', '1.0.0'), [], $paths, new Components(new ArrayObject()));
        $processor = $this->createProcessor();

        $result = $processor->process($openApi);
        $responses = $result->getPaths()->getPath('/customers')->getGet()?->getResponses();

        self::assertSame($response, $responses['200']);
    }

    public function testProcessSkipsNonResponseEntries(): void
    {
        $paths = new Paths();
        $operation = new Operation(responses: ['default' => ['description' => 'fallback']]);
        $paths->addPath('/customers', (new PathItem())->withGet($operation));

        $openApi = new OpenApi(new Info('Test', '1.0.0'), [], $paths, new Components(new ArrayObject()));
        $processor = $this->createProcessor();

        $result = $processor->process($openApi);

        self::assertSame($operation, $result->getPaths()->getPath('/customers')->getGet());
    }

    public function testProcessLeavesMediaTypeWithoutSchemaUntouched(): void
    {
        $paths = new Paths();
        $operation = new Operation(
            responses: [
                '200' => new Response(
                    description: 'ok',
                    content: new ArrayObject([
                        'application/ld+json' => new MediaType(),
                    ])
                ),
            ]
        );
        $paths->addPath(
            '/customers',
            (new PathItem())->withGet($operation)
        );

        $openApi = new OpenApi(new Info('Test', '1.0.0'), [], $paths, new Components(new ArrayObject()));
        $processor = $this->createProcessor();

        $result = $processor->process($openApi);
        self::assertSame($operation, $result->getPaths()->getPath('/customers')->getGet());
    }

    public function testProcessLeavesMediaTypeWithoutHydraFixesUntouched(): void
    {
        $paths = new Paths();
        $operation = new Operation(
            responses: [
                '200' => new Response(
                    description: 'ok',
                    content: new ArrayObject([
                        'application/ld+json' => new MediaType(new ArrayObject(['type' => 'object'])),
                    ])
                ),
            ]
        );
        $paths->addPath(
            '/customers',
            (new PathItem())->withGet($operation)
        );

        $openApi = new OpenApi(new Info('Test', '1.0.0'), [], $paths, new Components(new ArrayObject()));
        $processor = $this->createProcessor();

        $result = $processor->process($openApi);
        self::assertSame($operation, $result->getPaths()->getPath('/customers')->getGet());
    }

    public function testProcessUpdatesArrayContentDefinitionsWithHydraSchema(): void
    {
        $paths = new Paths();
        $response = new Response(
            description: 'ok',
            content: new ArrayObject([
                'application/json' => [
                    'schema' => [
                        'properties' => [
                            'view' => [
                                'example' => [
                                    '@id' => '/api/customers?page=1',
                                    'type' => 'PartialCollectionView',
                                ],
                            ],
                        ],
                    ],
                ],
            ])
        );
        $paths->addPath(
            '/customers',
            (new PathItem())->withGet(new Operation(responses: ['200' => $response]))
        );

        $openApi = new OpenApi(new Info('Test', '1.0.0'), [], $paths, new Components(new ArrayObject()));
        $processor = $this->createProcessor();

        $result = $processor->process($openApi);
        $responses = $result->getPaths()->getPath('/customers')->getGet()?->getResponses();
        $content = $responses['200']->getContent();

        self::assertInstanceOf(ArrayObject::class, $content);
        self::assertIsArray($content['application/json']);
        self::assertSame(
            'PartialCollectionView',
            $content['application/json']['schema']['properties']['view']['example']['@type']
        );
        self::assertArrayNotHasKey(
            'type',
            $content['application/json']['schema']['properties']['view']['example']
        );
    }

    public function testProcessPersistsNormalizedInlineSchemasWhenUpdaterMakesNoChanges(): void
    {
        $paths = new Paths();
        $response = new Response(
            description: 'ok',
            content: new ArrayObject([
                'application/json' => [
                    'schema' => new ArrayObject([
                        'type' => 'object',
                    ]),
                ],
            ])
        );
        $paths->addPath(
            '/customers',
            (new PathItem())->withGet(new Operation(responses: ['200' => $response]))
        );

        $openApi = new OpenApi(new Info('Test', '1.0.0'), [], $paths, new Components(new ArrayObject()));
        $processor = $this->createProcessor();

        $result = $processor->process($openApi);
        $responses = $result->getPaths()->getPath('/customers')->getGet()?->getResponses();
        $content = $responses['200']->getContent();

        self::assertInstanceOf(ArrayObject::class, $content);
        self::assertIsArray($content['application/json']);
        self::assertSame(
            ['type' => 'object'],
            $content['application/json']['schema']
        );
    }

    public function testProcessLeavesArrayContentDefinitionsWithUnchangedSchemaUntouched(): void
    {
        $paths = new Paths();
        $operation = new Operation(
            responses: [
                '200' => new Response(
                    description: 'ok',
                    content: new ArrayObject([
                        'application/json' => [
                            'schema' => [
                                'properties' => [
                                    'view' => [
                                        'example' => [
                                            '@id' => '/api/customers?page=1',
                                            '@type' => 'PartialCollectionView',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ])
                ),
            ]
        );
        $paths->addPath(
            '/customers',
            (new PathItem())->withGet($operation)
        );

        $openApi = new OpenApi(new Info('Test', '1.0.0'), [], $paths, new Components(new ArrayObject()));
        $processor = $this->createProcessor();

        $result = $processor->process($openApi);
        self::assertSame($operation, $result->getPaths()->getPath('/customers')->getGet());
    }

    private function createProcessor(): OpenApiSchemaFixesProcessor
    {
        $exampleUpdater = new HydraAtTypeExampleUpdater();
        $itemUpdater = new HydraAllOfItemUpdater($exampleUpdater);
        $allOfUpdater = new HydraAllOfUpdater($itemUpdater);
        $viewExampleUpdater = new HydraViewExampleUpdater(
            $allOfUpdater,
            new HydraDirectViewExampleUpdater()
        );
        $schemaNormalizer = new HydraSchemaNormalizer();
        $hydraFixer = new HydraCollectionSchemaFixer(
            $viewExampleUpdater,
            new HydraCollectionSchemasUpdater($schemaNormalizer, $viewExampleUpdater)
        );
        $definitionUpdater = new OpenApiContentDefinitionUpdater(
            new OpenApiMediaTypeSchemaFixer($hydraFixer),
            new OpenApiArrayContentSchemaUpdater($hydraFixer)
        );
        $contentUpdater = new OpenApiResponseContentUpdater($definitionUpdater);
        $responseFixer = new OpenApiResponseSchemaFixer($contentUpdater);
        $responsesUpdater = new OpenApiResponsesUpdater($responseFixer);

        return new OpenApiSchemaFixesProcessor(
            $hydraFixer,
            new OpenApiOperationSchemaFixer($responsesUpdater)
        );
    }
}
