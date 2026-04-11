<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Components;
use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Processor\ConstraintViolationPayloadItemsProcessor;
use App\Shared\Application\OpenApi\Processor\ConstraintViolationSchemaUpdater;
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

        $processor = $this->createProcessor();
        $result = $processor->process($openApi);

        $this->assertSame($openApi, $result);
    }

    public function testProcessReturnsOriginalWhenSchemasMissing(): void
    {
        $openApi = new OpenApi(new Info('Test', '1.0.0'), [], new Paths());

        $processor = $this->createProcessor();

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

        $processor = $this->createProcessor();
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
        $schemas = new ArrayObject(['ConstraintViolation-json' => $constraintViolation]);
        $components = new Components($schemas);
        $openApi = new OpenApi(new Info('Test', '1.0.0'), [], new Paths(), $components);

        $processor = $this->createProcessor();
        $result = $processor->process($openApi);

        $resultSchemas = $result->getComponents()->getSchemas();
        $this->assertInstanceOf(ArrayObject::class, $resultSchemas);
        $updatedSchema = $resultSchemas['ConstraintViolation-json'];
        $this->assertInstanceOf(ArrayObject::class, $updatedSchema);

        $schemaData = $updatedSchema->getArrayCopy();
        $payload = $schemaData['properties']['violations']['items']['properties']['payload'];
        $this->assertSame('array', $payload['type']);
        $this->assertSame(['type' => 'object'], $payload['items']);
    }

    public function testProcessAddsPayloadItemsWhenItemsIsNull(): void
    {
        $constraintViolation = new ArrayObject([
            'properties' => [
                'violations' => [
                    'items' => [
                        'properties' => [
                            'payload' => new ArrayObject([
                                'type' => 'array',
                                'items' => null,
                            ]),
                        ],
                    ],
                ],
            ],
        ]);
        $schemas = new ArrayObject(['ConstraintViolation' => $constraintViolation]);
        $components = new Components($schemas);
        $openApi = new OpenApi(new Info('Test', '1.0.0'), [], new Paths(), $components);

        $processor = $this->createProcessor();
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

    public function testProcessCreatesPayloadWhenMissing(): void
    {
        $constraintViolation = [
            'properties' => [
                'violations' => [
                    'items' => [
                        'properties' => [],
                    ],
                ],
            ],
        ];
        $schemas = new ArrayObject(['ConstraintViolation' => $constraintViolation]);
        $components = new Components($schemas);
        $openApi = new OpenApi(new Info('Test', '1.0.0'), [], new Paths(), $components);

        $processor = $this->createProcessor();
        $result = $processor->process($openApi);

        $resultSchemas = $result->getComponents()->getSchemas();
        $updatedSchema = $resultSchemas['ConstraintViolation'];
        $schemaData = $updatedSchema->getArrayCopy();
        $payload = $schemaData['properties']['violations']['items']['properties']['payload'];
        $code = $schemaData['properties']['violations']['items']['properties']['code'];
        $this->assertSame(['string', 'null'], $code['type']);
        $this->assertSame('The machine-readable violation code', $code['description']);
        $this->assertSame('array', $payload['type']);
        $this->assertSame(['type' => 'object'], $payload['items']);
    }

    public function testProcessPreservesExistingCodeProperty(): void
    {
        $constraintViolation = [
            'properties' => [
                'violations' => [
                    'items' => [
                        'properties' => [
                            'code' => [
                                'type' => 'string',
                                'description' => 'Existing description',
                            ],
                            'payload' => ['type' => 'array'],
                        ],
                    ],
                ],
            ],
        ];
        $schemas = new ArrayObject(['ConstraintViolation' => $constraintViolation]);
        $components = new Components($schemas);
        $openApi = new OpenApi(new Info('Test', '1.0.0'), [], new Paths(), $components);

        $processor = $this->createProcessor();
        $result = $processor->process($openApi);

        $resultSchemas = $result->getComponents()->getSchemas();
        $updatedSchema = $resultSchemas['ConstraintViolation'];
        $schemaData = $updatedSchema->getArrayCopy();
        $code = $schemaData['properties']['violations']['items']['properties']['code'];

        $this->assertSame('Existing description', $code['description']);
    }

    public function testProcessReturnsOriginalWhenSchemaMissing(): void
    {
        $schemas = new ArrayObject();
        $components = new Components($schemas);
        $openApi = new OpenApi(new Info('Test', '1.0.0'), [], new Paths(), $components);

        $this->assertProcessLeavesSchemasUnchanged($schemas, $openApi);
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
                                'items' => ['type' => 'object'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $schemas = new ArrayObject(['ConstraintViolation' => $constraintViolation]);
        $components = new Components($schemas);
        $openApi = new OpenApi(new Info('Test', '1.0.0'), [], new Paths(), $components);

        $this->assertProcessLeavesSchemasUnchanged($schemas, $openApi);
    }

    public function testProcessSkipsConstraintViolationSchemasWithUnsupportedShape(): void
    {
        $schemas = new ArrayObject([
            'ConstraintViolation' => new \stdClass(),
        ]);
        $components = new Components($schemas);
        $openApi = new OpenApi(new Info('Test', '1.0.0'), [], new Paths(), $components);

        $this->assertProcessLeavesSchemasUnchanged($schemas, $openApi);
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

        $this->assertProcessLeavesSchemasUnchanged($schemas, $openApi);
    }

    private function createProcessor(): ConstraintViolationPayloadItemsProcessor
    {
        return new ConstraintViolationPayloadItemsProcessor(
            new ConstraintViolationSchemaUpdater()
        );
    }

    private function assertProcessLeavesSchemasUnchanged(ArrayObject $schemas, OpenApi $openApi): void
    {
        $before = unserialize(serialize($schemas));
        $processor = $this->createProcessor();
        $result = $processor->process($openApi);

        $this->assertSame($openApi, $result);
        $this->assertEquals($before, $schemas);
    }
}
