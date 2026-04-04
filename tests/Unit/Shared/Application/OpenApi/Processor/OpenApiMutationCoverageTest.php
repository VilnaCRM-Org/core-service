<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Application\OpenApi\Processor\HydraAllOfItemUpdater;
use App\Shared\Application\OpenApi\Processor\HydraAllOfUpdater;
use App\Shared\Application\OpenApi\Processor\HydraAtTypeExampleUpdater;
use App\Shared\Application\OpenApi\Processor\HydraCollectionSchemaFixer;
use App\Shared\Application\OpenApi\Processor\HydraCollectionSchemasUpdater;
use App\Shared\Application\OpenApi\Processor\HydraDirectViewExampleUpdater;
use App\Shared\Application\OpenApi\Processor\HydraSchemaNormalizer;
use App\Shared\Application\OpenApi\Processor\HydraViewExampleUpdater;
use App\Shared\Application\OpenApi\Processor\OpenApiArrayContentSchemaUpdater;
use App\Shared\Application\OpenApi\Processor\OpenApiContentDefinitionUpdater;
use App\Shared\Application\OpenApi\Processor\OpenApiMediaTypeSchemaFixer;
use App\Shared\Application\OpenApi\Processor\OpenApiResponseContentUpdater;
use App\Shared\Application\OpenApi\Processor\OpenApiResponseSchemaFixer;
use App\Shared\Application\OpenApi\Processor\OpenApiResponsesUpdater;
use App\Tests\Unit\UnitTestCase;
use ArrayObject;

final class OpenApiMutationCoverageTest extends UnitTestCase
{
    public function testArrayContentSchemaUpdaterReturnsNullForUnchangedArraySchema(): void
    {
        $updater = new OpenApiArrayContentSchemaUpdater($this->createHydraCollectionSchemaFixer());

        self::assertNull($updater->update([
            'schema' => ['type' => 'object'],
            'example' => ['type' => 'plain'],
        ]));
    }

    public function testMediaTypeSchemaFixerLeavesMediaTypesWithoutSchemaUntouched(): void
    {
        $fixer = new OpenApiMediaTypeSchemaFixer($this->createHydraCollectionSchemaFixer());
        $mediaType = new MediaType();

        self::assertSame($mediaType, $fixer->fix($mediaType));
    }

    public function testMediaTypeSchemaFixerLeavesUnchangedArraySchemaUntouched(): void
    {
        $fixer = new OpenApiMediaTypeSchemaFixer($this->createHydraCollectionSchemaFixer());
        $mediaType = new MediaType(new ArrayObject(['type' => 'object']));

        self::assertSame($mediaType, $fixer->fix($mediaType));
    }

    public function testResponseContentUpdaterContinuesPastUnchangedDefinitions(): void
    {
        $updater = new OpenApiResponseContentUpdater($this->createContentDefinitionUpdater());
        $content = new ArrayObject([
            'application/problem+json' => ['schema' => ['type' => 'object']],
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
        ]);

        $updatedContent = $updater->update($content);

        self::assertInstanceOf(ArrayObject::class, $updatedContent);
        self::assertCount(2, $updatedContent);
        self::assertSame(
            ['schema' => ['type' => 'object']],
            $updatedContent['application/problem+json']
        );
        self::assertSame(
            'PartialCollectionView',
            $updatedContent['application/json']['schema']['properties']['view']['example']['@type']
        );
        self::assertArrayNotHasKey(
            'type',
            $updatedContent['application/json']['schema']['properties']['view']['example']
        );
    }

    public function testResponsesUpdaterContinuesPastUnchangedResponses(): void
    {
        $updater = new OpenApiResponsesUpdater(
            new OpenApiResponseSchemaFixer(
                new OpenApiResponseContentUpdater($this->createContentDefinitionUpdater())
            )
        );
        $unchangedResponse = new Response(
            description: 'ok',
            content: new ArrayObject([
                'application/problem+json' => ['schema' => ['type' => 'object']],
            ])
        );
        $changedResponse = new Response(
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

        $updatedResponses = $updater->update([
            '200' => $unchangedResponse,
            '206' => $changedResponse,
        ]);

        self::assertIsArray($updatedResponses);
        self::assertCount(2, $updatedResponses);
        self::assertSame($unchangedResponse, $updatedResponses['200']);
        self::assertNotSame($changedResponse, $updatedResponses['206']);
        self::assertSame(
            'PartialCollectionView',
            $updatedResponses['206']->getContent()['application/json']['schema']['properties']['view']['example']['@type']
        );
        self::assertArrayNotHasKey(
            'type',
            $updatedResponses['206']->getContent()['application/json']['schema']['properties']['view']['example']
        );
    }

    private function createContentDefinitionUpdater(): OpenApiContentDefinitionUpdater
    {
        $hydraFixer = $this->createHydraCollectionSchemaFixer();

        return new OpenApiContentDefinitionUpdater(
            new OpenApiMediaTypeSchemaFixer($hydraFixer),
            new OpenApiArrayContentSchemaUpdater($hydraFixer)
        );
    }

    private function createHydraCollectionSchemaFixer(): HydraCollectionSchemaFixer
    {
        $exampleUpdater = new HydraAtTypeExampleUpdater();
        $itemUpdater = new HydraAllOfItemUpdater($exampleUpdater);
        $allOfUpdater = new HydraAllOfUpdater($itemUpdater);
        $viewExampleUpdater = new HydraViewExampleUpdater(
            $allOfUpdater,
            new HydraDirectViewExampleUpdater()
        );

        return new HydraCollectionSchemaFixer(
            $viewExampleUpdater,
            new HydraCollectionSchemasUpdater(new HydraSchemaNormalizer(), $viewExampleUpdater)
        );
    }
}
