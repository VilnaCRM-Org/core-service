<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Components;
use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Processor\HydraAllOfItemUpdater;
use App\Shared\Application\OpenApi\Processor\HydraAllOfUpdater;
use App\Shared\Application\OpenApi\Processor\HydraAtTypeExampleUpdater;
use App\Shared\Application\OpenApi\Processor\HydraCollectionSchemaFixer;
use App\Shared\Application\OpenApi\Processor\HydraSchemaNormalizer;
use App\Shared\Application\OpenApi\Processor\HydraViewExampleUpdater;
use App\Shared\Application\OpenApi\Processor\OpenApiSchemaFixesProcessor;
use App\Shared\Application\OpenApi\Processor\SchemaNormalizer;
use App\Shared\Application\OpenApi\Processor\UlidSchemaFixer;
use App\Tests\Unit\UnitTestCase;
use ArrayObject;

final class OpenApiSchemaFixesProcessorTest extends UnitTestCase
{
    public function testProcessUpdatesHydraViewExampleAndUlidSchema(): void
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
            'UlidInterface.jsonld-output' => new ArrayObject([
                'description' => 'Ulid',
                'deprecated' => false,
                'properties' => [
                    '@id' => ['type' => 'string'],
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

        $this->assertArrayHasKey('@type', $example);
        $this->assertArrayNotHasKey('type', $example);

        $ulidSchema = SchemaNormalizer::normalize(
            $resultSchemas['UlidInterface.jsonld-output']
        );
        $this->assertSame('string', $ulidSchema['type']);
        $this->assertSame('Ulid', $ulidSchema['description']);
        $this->assertFalse($ulidSchema['deprecated']);
    }

    public function testProcessLeavesSchemasWhenHydraAndUlidMissing(): void
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

        $this->assertArrayHasKey('@type', $example);
        $this->assertArrayNotHasKey('type', $example);
    }

    public function testProcessLeavesHydraExampleWhenAtTypeAlreadySet(): void
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

        $this->assertArrayHasKey('@type', $example);
        $this->assertArrayHasKey('type', $example);
    }

    public function testProcessDefaultsUlidDeprecatedToFalse(): void
    {
        $schemas = new ArrayObject([
            'UlidInterface.jsonld-output' => new ArrayObject([
                'description' => 'Ulid',
                'properties' => [
                    '@id' => ['type' => 'string'],
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
        $ulidSchema = SchemaNormalizer::normalize(
            $resultSchemas['UlidInterface.jsonld-output']
        );

        $this->assertSame('string', $ulidSchema['type']);
        $this->assertSame('Ulid', $ulidSchema['description']);
        $this->assertFalse($ulidSchema['deprecated']);
    }

    public function testProcessPreservesUlidDeprecatedTrue(): void
    {
        $schemas = new ArrayObject([
            'UlidInterface.jsonld-output' => new ArrayObject([
                'description' => 'Ulid',
                'deprecated' => true,
                'properties' => [
                    '@id' => ['type' => 'string'],
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
        $ulidSchema = SchemaNormalizer::normalize(
            $resultSchemas['UlidInterface.jsonld-output']
        );

        $this->assertSame('string', $ulidSchema['type']);
        $this->assertTrue($ulidSchema['deprecated']);
    }

    private function createProcessor(): OpenApiSchemaFixesProcessor
    {
        $exampleUpdater = new HydraAtTypeExampleUpdater();
        $itemUpdater = new HydraAllOfItemUpdater($exampleUpdater);
        $allOfUpdater = new HydraAllOfUpdater($itemUpdater);
        $viewExampleUpdater = new HydraViewExampleUpdater($allOfUpdater);
        $schemaNormalizer = new HydraSchemaNormalizer();
        $hydraFixer = new HydraCollectionSchemaFixer($schemaNormalizer, $viewExampleUpdater);
        $ulidFixer = new UlidSchemaFixer();

        return new OpenApiSchemaFixesProcessor($hydraFixer, $ulidFixer);
    }
}
