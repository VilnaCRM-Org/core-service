<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Components;
use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Processor\ConstraintViolationPayloadItemsProcessor;
use App\Tests\Unit\UnitTestCase;
use ArrayObject;

final class ConstraintViolationPayloadItemsProcessorTest extends UnitTestCase
{
    public function testProcessReturnsOriginalWhenSchemasIsNull(): void
    {
        // Returns original OpenApi since there is no ConstraintViolation schema to update
        $components = $this->createMock(Components::class);
        $components->method('getSchemas')->willReturn(null);

        $openApi = new OpenApi(
            new Info('Test', '1.0.0'),
            [],
            new Paths(),
            $components
        );

        $processor = new ConstraintViolationPayloadItemsProcessor();
        $result = $processor->process($openApi);

        $this->assertSame($openApi, $result);
    }

    public function testProcessReturnsOriginalWhenSchemasMissing(): void
    {
        $openApi = new OpenApi(new Info('Test', '1.0.0'), [], new Paths());

        $processor = new ConstraintViolationPayloadItemsProcessor();

        $this->assertSame($openApi, $processor->process($openApi));
    }

    public function testProcessAddsPayloadItemsWhenMissing(): void
    {
        $constraintViolation = new ArrayObject([
            'properties' => [
                'violations' => [
                    'items' => [
                        'properties' => [
                            'payload' => new ArrayObject(['type' => 'array']),
                        ],
                    ],
                ],
            ],
        ]);
        $schemas = new ArrayObject(['ConstraintViolation' => $constraintViolation]);
        $components = new Components($schemas);
        $openApi = new OpenApi(new Info('Test', '1.0.0'), [], new Paths(), $components);

        $processor = new ConstraintViolationPayloadItemsProcessor();
        $result = $processor->process($openApi);

        $resultSchemas = $result->getComponents()->getSchemas();
        $this->assertInstanceOf(ArrayObject::class, $resultSchemas);
        $updatedSchema = $resultSchemas['ConstraintViolation'];
        $this->assertInstanceOf(ArrayObject::class, $updatedSchema);

        $schemaData = $updatedSchema->getArrayCopy();
        $payload = $schemaData['properties']['violations']['items']['properties']['payload'];
        $this->assertSame('array', $payload['type']);
        $this->assertSame(['type' => 'object'], $payload['items']);
    }

    public function testProcessAddsPayloadItemsWhenSchemaIsArray(): void
    {
        $constraintViolation = [
            'properties' => [
                'violations' => [
                    'items' => [
                        'properties' => [
                            'payload' => ['type' => 'array'],
                        ],
                    ],
                ],
            ],
        ];
        $schemas = new ArrayObject(['ConstraintViolation' => $constraintViolation]);
        $components = new Components($schemas);
        $openApi = new OpenApi(new Info('Test', '1.0.0'), [], new Paths(), $components);

        $processor = new ConstraintViolationPayloadItemsProcessor();
        $result = $processor->process($openApi);

        $resultSchemas = $result->getComponents()->getSchemas();
        $this->assertInstanceOf(ArrayObject::class, $resultSchemas);
        $updatedSchema = $resultSchemas['ConstraintViolation'];
        $this->assertInstanceOf(ArrayObject::class, $updatedSchema);

        $schemaData = $updatedSchema->getArrayCopy();
        $payload = $schemaData['properties']['violations']['items']['properties']['payload'];
        $this->assertSame('array', $payload['type']);
        $this->assertSame(['type' => 'object'], $payload['items']);
    }

    public function testProcessReturnsOriginalWhenSchemaMissing(): void
    {
        $schemas = new ArrayObject();
        $components = new Components($schemas);
        $openApi = new OpenApi(new Info('Test', '1.0.0'), [], new Paths(), $components);

        $processor = new ConstraintViolationPayloadItemsProcessor();

        $this->assertSame($openApi, $processor->process($openApi));
    }

    public function testProcessReturnsOriginalWhenPayloadItemsAlreadyDefined(): void
    {
        $constraintViolation = [
            'properties' => [
                'violations' => [
                    'items' => [
                        'properties' => [
                            'payload' => [
                                'type' => 'array',
                                'items' => ['type' => 'string'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $schemas = new ArrayObject(['ConstraintViolation' => $constraintViolation]);
        $components = new Components($schemas);
        $openApi = new OpenApi(new Info('Test', '1.0.0'), [], new Paths(), $components);

        $processor = new ConstraintViolationPayloadItemsProcessor();

        $this->assertSame($openApi, $processor->process($openApi));
    }

    public function testProcessSkipsNonConstraintViolationSchemas(): void
    {
        // Includes a ConstraintViolation schema, but not in a shape the processor updates.
        $otherSchema = ['type' => 'object'];
        $constraintViolation = ['type' => 'object'];
        $schemas = new ArrayObject([
            'OtherSchema' => $otherSchema,
            'ConstraintViolation' => $constraintViolation,
        ]);
        $components = new Components($schemas);
        $openApi = new OpenApi(new Info('Test', '1.0.0'), [], new Paths(), $components);

        $processor = new ConstraintViolationPayloadItemsProcessor();
        $result = $processor->process($openApi);

        // Should return original since ConstraintViolation has no violations property to update
        $this->assertSame($openApi, $result);
    }
}
