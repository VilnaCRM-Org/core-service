<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use App\Shared\Application\OpenApi\Transformer\IriReferenceMediaTypeTransformer;
use App\Shared\Application\OpenApi\Transformer\IriReferencePropertyTransformer;
use App\Tests\Unit\Shared\Application\OpenApi\Stub\RecordingPropertyTransformer;
use App\Tests\Unit\UnitTestCase;

final class IriReferenceMediaTypeTransformerTest extends UnitTestCase
{
    private IriReferenceMediaTypeTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transformer = new IriReferenceMediaTypeTransformer(
            new IriReferencePropertyTransformer()
        );
    }

    public function testTransformReturnsOriginalWhenSchemaIsMissing(): void
    {
        $mediaType = [
            'schema' => ['example' => ['type' => 'string']],
            'description' => 'kept',
        ];

        self::assertSame($mediaType, $this->transformer->transform($mediaType));
    }

    public function testTransformRewritesNestedIriReferenceProperties(): void
    {
        $mediaType = [
            'schema' => [
                'properties' => [
                    'customer' => ['type' => 'iri-reference'],
                    'title' => ['type' => 'string'],
                ],
            ],
        ];

        $result = $this->transformer->transform($mediaType);

        self::assertSame('string', $result['schema']['properties']['customer']['type']);
        self::assertSame('iri-reference', $result['schema']['properties']['customer']['format']);
        self::assertSame(
            $mediaType['schema']['properties']['title'],
            $result['schema']['properties']['title']
        );
    }

    public function testUsesInjectedPropertyTransformer(): void
    {
        $propertyTransformer = new RecordingPropertyTransformer();
        $transformer = new IriReferenceMediaTypeTransformer($propertyTransformer);

        $mediaType = [
            'schema' => [
                'properties' => [
                    'customer' => ['type' => 'iri-reference'],
                ],
            ],
        ];

        $result = $transformer->transform($mediaType);

        self::assertTrue($propertyTransformer->wasInvoked());
        self::assertSame(
            RecordingPropertyTransformer::TRANSFORMED_FLAG,
            $result['schema']['properties']['customer']
        );
    }

    public function testTransformPreservesAdditionalKeysWhenSchemaMissing(): void
    {
        $mediaType = [
            'schema' => null,
            'example' => ['foo' => 'bar'],
        ];

        $result = $this->transformer->transform($mediaType);

        self::assertArrayHasKey('example', $result);
        self::assertSame($mediaType['example'], $result['example']);
    }

    public function testTransformPreservesAdditionalKeysWhenPropertiesUnchanged(): void
    {
        $mediaType = [
            'schema' => ['properties' => []],
            'example' => ['foo' => 'bar'],
        ];

        $result = $this->transformer->transform($mediaType);

        self::assertArrayHasKey('example', $result);
    }

    public function testTransformPreservesAdditionalKeysWhenPropertiesUpdated(): void
    {
        $mediaType = [
            'schema' => [
                'properties' => [
                    'customer' => ['type' => 'iri-reference'],
                ],
            ],
            'example' => ['foo' => 'bar'],
        ];

        $result = $this->transformer->transform($mediaType);

        self::assertArrayHasKey('example', $result);
    }
}
