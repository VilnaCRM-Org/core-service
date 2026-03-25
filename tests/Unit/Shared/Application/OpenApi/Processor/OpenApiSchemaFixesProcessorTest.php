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

    public function testProcessCreatesComponentsWhenMissing(): void
    {
        $openApi = new OpenApi(
            new Info('Test', '1.0.0'),
            [],
            new Paths(),
            null
        );

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

        $this->assertSame('Collection', $example['@type']);
        $this->assertSame('Collection', $example['type']);
    }

    private function createProcessor(): OpenApiSchemaFixesProcessor
    {
        $exampleUpdater = new HydraAtTypeExampleUpdater();
        $itemUpdater = new HydraAllOfItemUpdater($exampleUpdater);
        $allOfUpdater = new HydraAllOfUpdater($itemUpdater);
        $viewExampleUpdater = new HydraViewExampleUpdater($allOfUpdater);
        $schemaNormalizer = new HydraSchemaNormalizer();
        $hydraFixer = new HydraCollectionSchemaFixer($schemaNormalizer, $viewExampleUpdater);

        return new OpenApiSchemaFixesProcessor($hydraFixer);
    }
}
