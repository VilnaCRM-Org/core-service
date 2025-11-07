<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use App\Shared\Application\OpenApi\Processor\ContentPropertyProcessor;
use App\Shared\Application\OpenApi\Processor\PropertyTypeFixer;
use App\Tests\Unit\UnitTestCase;
use ArrayObject;

final class ContentPropertyProcessorTest extends UnitTestCase
{
    private ContentPropertyProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();
        $propertyTypeFixer = new PropertyTypeFixer();
        $this->processor = new ContentPropertyProcessor($propertyTypeFixer);
    }

    public function testProcessReturnsTrueWhenIriReferenceFixed(): void
    {
        $content = new ArrayObject([
            'application/json' => [
                'schema' => [
                    'properties' => [
                        'relation' => ['type' => 'iri-reference'],
                    ],
                ],
            ],
        ]);

        $result = $this->processor->process($content);

        $this->assertTrue($result);
        $this->assertEquals('string', $content['application/json']['schema']['properties']['relation']['type']);
        $this->assertEquals('iri-reference', $content['application/json']['schema']['properties']['relation']['format']);
    }

    public function testProcessReturnsFalseWhenNoIriReference(): void
    {
        $content = new ArrayObject([
            'application/json' => [
                'schema' => [
                    'properties' => [
                        'name' => ['type' => 'string'],
                    ],
                ],
            ],
        ]);

        $result = $this->processor->process($content);

        $this->assertFalse($result);
        $this->assertEquals('string', $content['application/json']['schema']['properties']['name']['type']);
    }

    public function testProcessHandlesMultipleProperties(): void
    {
        $content = new ArrayObject([
            'application/json' => [
                'schema' => [
                    'properties' => [
                        'relation1' => ['type' => 'iri-reference'],
                        'name' => ['type' => 'string'],
                        'relation2' => ['type' => 'iri-reference'],
                    ],
                ],
            ],
        ]);

        $result = $this->processor->process($content);

        $this->assertTrue($result);
        $props = $content['application/json']['schema']['properties'];
        $this->assertEquals('string', $props['relation1']['type']);
        $this->assertEquals('iri-reference', $props['relation1']['format']);
        $this->assertEquals('string', $props['name']['type']);
        $this->assertArrayNotHasKey('format', $props['name']);
        $this->assertEquals('string', $props['relation2']['type']);
        $this->assertEquals('iri-reference', $props['relation2']['format']);
    }

    public function testProcessHandlesMultipleMediaTypes(): void
    {
        $content = new ArrayObject([
            'application/json' => [
                'schema' => [
                    'properties' => [
                        'relation' => ['type' => 'iri-reference'],
                    ],
                ],
            ],
            'application/xml' => [
                'schema' => [
                    'properties' => [
                        'xmlRelation' => ['type' => 'iri-reference'],
                    ],
                ],
            ],
        ]);

        $result = $this->processor->process($content);

        $this->assertTrue($result);
        $this->assertEquals('string', $content['application/json']['schema']['properties']['relation']['type']);
        $this->assertEquals('string', $content['application/xml']['schema']['properties']['xmlRelation']['type']);
    }

    public function testProcessHandlesSchemaWithoutProperties(): void
    {
        $content = new ArrayObject([
            'application/json' => [
                'schema' => [
                    'type' => 'object',
                ],
            ],
        ]);

        $result = $this->processor->process($content);

        $this->assertFalse($result);
        $this->assertArrayNotHasKey('properties', $content['application/json']['schema']);
    }

    public function testProcessHandlesEmptyContent(): void
    {
        $content = new ArrayObject([]);

        $result = $this->processor->process($content);

        $this->assertFalse($result);
    }
}
