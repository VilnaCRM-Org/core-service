<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use App\Shared\Application\OpenApi\Processor\IriReferenceContentTransformer;
use App\Shared\Application\OpenApi\Processor\IriReferenceMediaTypeTransformer;
use App\Shared\Application\OpenApi\Processor\IriReferenceMediaTypeTransformerInterface;
use App\Tests\Unit\UnitTestCase;
use ArrayObject;

final class IriReferenceContentTransformerTest extends UnitTestCase
{
    private IriReferenceContentTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transformer = new IriReferenceContentTransformer();
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

        self::assertTrue($mediaTypeTransformer->invoked);
        self::assertSame(
            RecordingMediaTypeTransformer::TRANSFORMED_FLAG,
            $result['application/json']
        );
    }
}

final class RecordingMediaTypeTransformer implements IriReferenceMediaTypeTransformerInterface
{
    public const TRANSFORMED_FLAG = ['transformed' => true];

    public bool $invoked = false;

    public function transform(array $mediaType): array
    {
        $this->invoked = true;

        return self::TRANSFORMED_FLAG;
    }
}
