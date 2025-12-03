<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use App\Shared\Application\OpenApi\Transformer\IriReferenceContentTransformer;
use App\Shared\Application\OpenApi\Transformer\IriReferenceMediaTypeTransformer;
use App\Shared\Application\OpenApi\Transformer\IriReferencePropertyTransformer;
use App\Tests\Unit\Shared\Application\OpenApi\Stub\RecordingMediaTypeTransformer;
use App\Tests\Unit\UnitTestCase;
use ArrayObject;

final class IriReferenceContentTransformerTest extends UnitTestCase
{
    private IriReferenceContentTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transformer = new IriReferenceContentTransformer(
            new IriReferenceMediaTypeTransformer(
                new IriReferencePropertyTransformer()
            )
        );
    }

    public function testTransformReturnsNullWhenNothingChanges(): void
    {
        $content = new ArrayObject([
            'application/json' => ['schema' => ['properties' => ['name' => ['type' => 'string']]]],
        ]);

        self::assertNull($this->transformer->transform($content));
    }

    public function testTransformSkipsNonArrayDefinitionsWithoutStopping(): void
    {
        $content = new ArrayObject([
            'text/plain' => 'raw',
            'application/json' => [
                'schema' => [
                    'properties' => [
                        'relation' => ['type' => 'iri-reference'],
                    ],
                ],
            ],
        ]);

        $result = $this->transformer->transform($content);

        self::assertNotNull($result);
        self::assertArrayHasKey('relation', $result['application/json']['schema']['properties']);
    }

    public function testTransformContinuesAfterUnchangedEntries(): void
    {
        $content = new ArrayObject([
            'application/problem+json' => [
                'schema' => [
                    'properties' => [
                        'status' => ['type' => 'integer'],
                    ],
                ],
            ],
            'application/json' => [
                'schema' => [
                    'properties' => [
                        'relation' => ['type' => 'iri-reference'],
                    ],
                ],
            ],
        ]);

        $result = $this->transformer->transform($content);

        self::assertNotNull($result);
        self::assertArrayHasKey('format', $result['application/json']['schema']['properties']['relation']);
    }

    public function testTransformIgnoresNonArrayDefinitions(): void
    {
        $content = new ArrayObject(['text/plain' => 'scalar-definition']);

        self::assertNull($this->transformer->transform($content));
    }

    public function testTransformReturnsNormalizedContentWhenChangesDetected(): void
    {
        $content = new ArrayObject([
            'application/json' => [
                'schema' => [
                    'properties' => [
                        'status' => ['type' => 'iri-reference'],
                    ],
                ],
            ],
        ]);

        $result = $this->transformer->transform($content);

        self::assertNotNull($result);
        self::assertSame(
            'iri-reference',
            $result['application/json']['schema']['properties']['status']['format']
        );
    }

    public function testUsesInjectedMediaTypeTransformer(): void
    {
        $mediaTypeTransformer = new RecordingMediaTypeTransformer();
        $transformer = new IriReferenceContentTransformer($mediaTypeTransformer);

        $content = new ArrayObject([
            'application/json' => [
                'schema' => [
                    'properties' => [
                        'status' => ['type' => 'iri-reference'],
                    ],
                ],
            ],
        ]);

        $result = $transformer->transform($content);

        self::assertTrue($mediaTypeTransformer->wasInvoked());
        self::assertSame(
            RecordingMediaTypeTransformer::TRANSFORMED_FLAG,
            $result['application/json']
        );
    }
}
