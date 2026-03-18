<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Components;
use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Processor\UlidInterfaceSchemaFixer;
use App\Tests\Unit\UnitTestCase;
use ArrayObject;

final class UlidInterfaceSchemaFixerTest extends UnitTestCase
{
    private UlidInterfaceSchemaFixer $fixer;

    protected function setUp(): void
    {
        $this->fixer = new UlidInterfaceSchemaFixer();
    }

    public function testAddsUlidPropertyToUlidInterfaceSchema(): void
    {
        $schemas = new ArrayObject([
            'UlidInterface.jsonld-output' => [
                'type' => 'object',
                'properties' => [],
            ],
        ]);

        $openApi = $this->createOpenApi($schemas);
        $result = $this->fixer->process($openApi);

        $resultSchemas = $result->getComponents()->getSchemas();
        $ulidInterface = $resultSchemas['UlidInterface.jsonld-output'];

        self::assertArrayHasKey('properties', $ulidInterface);
        self::assertArrayHasKey('ulid', $ulidInterface['properties']);
        self::assertSame(['type' => 'string'], $ulidInterface['properties']['ulid']);
    }

    public function testDoesNotOverwriteExistingUlidProperty(): void
    {
        $schemas = new ArrayObject([
            'UlidInterface.jsonld-output' => [
                'type' => 'object',
                'properties' => [
                    'ulid' => ['type' => 'string', 'format' => 'ulid'],
                ],
            ],
        ]);

        $openApi = $this->createOpenApi($schemas);
        $result = $this->fixer->process($openApi);

        $resultSchemas = $result->getComponents()->getSchemas();
        $ulidInterface = $resultSchemas['UlidInterface.jsonld-output'];

        self::assertSame(['type' => 'string', 'format' => 'ulid'], $ulidInterface['properties']['ulid']);
    }

    public function testCreatesUlidInterfaceSchemaWhenMissing(): void
    {
        $schemas = new ArrayObject([
            'SomeOther.jsonld-output' => ['type' => 'object'],
        ]);

        $openApi = $this->createOpenApi($schemas);
        $result = $this->fixer->process($openApi);

        $resultSchemas = $result->getComponents()->getSchemas();

        // When UlidInterface schema is missing, it should be created with ulid property
        self::assertArrayHasKey('UlidInterface.jsonld-output', $resultSchemas);
        self::assertArrayHasKey('properties', $resultSchemas['UlidInterface.jsonld-output']);
        self::assertArrayHasKey('ulid', $resultSchemas['UlidInterface.jsonld-output']['properties']);
        self::assertSame(
            ['type' => 'string'],
            $resultSchemas['UlidInterface.jsonld-output']['properties']['ulid']
        );
    }

    public function testReturnsEarlyWhenSchemasNull(): void
    {
        $openApi = $this->createOpenApi(null);
        $result = $this->fixer->process($openApi);

        $resultSchemas = $result->getComponents()->getSchemas();

        self::assertNull($resultSchemas);
    }

    public function testReplacesUlidRefWithStringTypeInCustomerSchema(): void
    {
        $schemas = new ArrayObject([
            'UlidInterface.jsonld-output' => [
                'type' => 'object',
                'properties' => [],
            ],
            'Customer.jsonld-output' => [
                'type' => 'object',
                'properties' => [
                    'ulid' => ['$ref' => '#/components/schemas/UlidInterface.jsonld-output'],
                ],
            ],
        ]);

        $openApi = $this->createOpenApi($schemas);
        $result = $this->fixer->process($openApi);

        $resultSchemas = $result->getComponents()->getSchemas();
        $customer = $resultSchemas['Customer.jsonld-output'];

        self::assertSame(['type' => 'string'], $customer['properties']['ulid']);
    }

    public function testReplacesUlidRefWithStringTypeInCustomerTypeSchema(): void
    {
        $schemas = new ArrayObject([
            'UlidInterface.jsonld-output' => [
                'type' => 'object',
                'properties' => [],
            ],
            'CustomerType.jsonld-output' => [
                'type' => 'object',
                'properties' => [
                    'ulid' => ['$ref' => '#/components/schemas/UlidInterface.jsonld-output'],
                ],
            ],
        ]);

        $openApi = $this->createOpenApi($schemas);
        $result = $this->fixer->process($openApi);

        $resultSchemas = $result->getComponents()->getSchemas();
        $customerType = $resultSchemas['CustomerType.jsonld-output'];

        self::assertSame(['type' => 'string'], $customerType['properties']['ulid']);
    }

    public function testDoesNotReplaceNonUlidRef(): void
    {
        $schemas = new ArrayObject([
            'Customer.jsonld-output' => [
                'type' => 'object',
                'properties' => [
                    'ulid' => ['$ref' => '#/components/schemas/SomeOtherSchema'],
                ],
            ],
        ]);

        $openApi = $this->createOpenApi($schemas);
        $result = $this->fixer->process($openApi);

        $resultSchemas = $result->getComponents()->getSchemas();
        $customer = $resultSchemas['Customer.jsonld-output'];

        self::assertSame(['$ref' => '#/components/schemas/SomeOtherSchema'], $customer['properties']['ulid']);
    }

    public function testDoesNotReplaceUlidWhenNoRefPresent(): void
    {
        $schemas = new ArrayObject([
            'Customer.jsonld-output' => [
                'type' => 'object',
                'properties' => [
                    'ulid' => ['type' => 'string'],
                ],
            ],
        ]);

        $openApi = $this->createOpenApi($schemas);
        $result = $this->fixer->process($openApi);

        $resultSchemas = $result->getComponents()->getSchemas();
        $customer = $resultSchemas['Customer.jsonld-output'];

        self::assertSame(['type' => 'string'], $customer['properties']['ulid']);
    }

    public function testDoesNotReplaceUlidWhenPropertyMissing(): void
    {
        $schemas = new ArrayObject([
            'Customer.jsonld-output' => [
                'type' => 'object',
                'properties' => [
                    'name' => ['type' => 'string'],
                ],
            ],
        ]);

        $openApi = $this->createOpenApi($schemas);
        $result = $this->fixer->process($openApi);

        $resultSchemas = $result->getComponents()->getSchemas();
        $customer = $resultSchemas['Customer.jsonld-output'];

        self::assertArrayNotHasKey('ulid', $customer['properties']);
    }

    public function testDoesNotReplaceWhenRefIsNotString(): void
    {
        $schemas = new ArrayObject([
            'Customer.jsonld-output' => [
                'type' => 'object',
                'properties' => [
                    'ulid' => ['$ref' => 123],
                ],
            ],
        ]);

        $openApi = $this->createOpenApi($schemas);
        $result = $this->fixer->process($openApi);

        $resultSchemas = $result->getComponents()->getSchemas();
        $customer = $resultSchemas['Customer.jsonld-output'];

        self::assertSame(['$ref' => 123], $customer['properties']['ulid']);
    }

    public function testHandlesNonArraySchemaProperty(): void
    {
        // Test when Customer schema value itself is not an array/ArrayObject (e.g., string)
        // This triggers the hasUlidProperty early return for non-array schema
        $schemas = new ArrayObject([
            'Customer.jsonld-output' => 'not-an-array',
        ]);

        $openApi = $this->createOpenApi($schemas);
        $result = $this->fixer->process($openApi);

        // Should not modify the schema when schema itself is not an array/ArrayObject
        $resultSchemas = $result->getComponents()->getSchemas();
        $customer = $resultSchemas['Customer.jsonld-output'];

        self::assertSame('not-an-array', $customer);
    }

    public function testHandlesUlidPropertyAsArrayObject(): void
    {
        // Test when ulid property in Customer schema is an ArrayObject (production case)
        $schemas = new ArrayObject([
            'Customer.jsonld-output' => [
                'type' => 'object',
                'properties' => [
                    'ulid' => new ArrayObject(['$ref' => '#/components/schemas/UlidInterface.jsonld-output']),
                ],
            ],
        ]);

        $openApi = $this->createOpenApi($schemas);
        $result = $this->fixer->process($openApi);

        $resultSchemas = $result->getComponents()->getSchemas();
        $customer = $resultSchemas['Customer.jsonld-output'];

        // ArrayObject with UlidInterface ref should be replaced with string type
        self::assertSame(['type' => 'string'], $customer['properties']['ulid']);
    }

    public function testHandlesMultipleSchemaRefTypes(): void
    {
        $schemas = new ArrayObject([
            'Customer.jsonld-output' => [
                'type' => 'object',
                'properties' => [
                    'ulid' => ['$ref' => '#/components/schemas/UlidInterface.jsonld-output'],
                ],
            ],
            'CustomerType.jsonld-output' => [
                'type' => 'object',
                'properties' => [
                    'ulid' => ['$ref' => '#/components/schemas/UlidInterface.jsonld-output'],
                ],
            ],
        ]);

        $openApi = $this->createOpenApi($schemas);
        $result = $this->fixer->process($openApi);

        $resultSchemas = $result->getComponents()->getSchemas();

        self::assertSame(['type' => 'string'], $resultSchemas['Customer.jsonld-output']['properties']['ulid']);
        self::assertSame(['type' => 'string'], $resultSchemas['CustomerType.jsonld-output']['properties']['ulid']);
    }

    public function testPreservesUnfixedSchemasWhenMultiple(): void
    {
        // Test that when processing multiple schemas, schemas that don't need fixing
        // are preserved while schemas that need fixing are modified.
        // This catches potential mutation where early return could drop other schemas.
        $schemas = new ArrayObject([
            'UlidInterface.jsonld-output' => [
                'type' => 'object',
                'properties' => [],
            ],
            'Customer.jsonld-output' => [
                'type' => 'object',
                'properties' => [
                    'ulid' => ['$ref' => '#/components/schemas/UlidInterface.jsonld-output'],
                ],
            ],
            'Product.jsonld-output' => [
                'type' => 'object',
                'properties' => [
                    'ulid' => ['$ref' => '#/components/schemas/SomeOtherSchema'],
                ],
            ],
        ]);

        $openApi = $this->createOpenApi($schemas);
        $result = $this->fixer->process($openApi);

        $resultSchemas = $result->getComponents()->getSchemas();

        // Customer gets fixed (UlidInterface ref -> string type)
        self::assertSame(['type' => 'string'], $resultSchemas['Customer.jsonld-output']['properties']['ulid']);
        // Product is preserved untouched (non-UlidInterface ref)
        self::assertSame(['$ref' => '#/components/schemas/SomeOtherSchema'], $resultSchemas['Product.jsonld-output']['properties']['ulid']);
        // UlidInterface schema is also present
        self::assertArrayHasKey('UlidInterface.jsonld-output', $resultSchemas);
    }

    public function testHandlesUlidInterfaceSchemaAsArrayObject(): void
    {
        // Test with ArrayObject as schema value (production case from OpenApi)
        $schemas = new ArrayObject([
            'UlidInterface.jsonld-output' => new ArrayObject([
                'type' => 'object',
                'properties' => new ArrayObject(),
            ]),
        ]);

        $openApi = $this->createOpenApi($schemas);
        $result = $this->fixer->process($openApi);

        $resultSchemas = $result->getComponents()->getSchemas();
        $ulidInterface = $resultSchemas['UlidInterface.jsonld-output'];

        // ArrayObject should be treated as non-array and get default empty array
        self::assertArrayHasKey('properties', $ulidInterface);
    }

    public function testHandlesUlidInterfaceWithoutPropertiesKey(): void
    {
        // Test when UlidInterface exists but has no 'properties' key
        $schemas = new ArrayObject([
            'UlidInterface.jsonld-output' => ['type' => 'object'],
        ]);

        $openApi = $this->createOpenApi($schemas);
        $result = $this->fixer->process($openApi);

        $resultSchemas = $result->getComponents()->getSchemas();
        $ulidInterface = $resultSchemas['UlidInterface.jsonld-output'];

        // Should add ulid property
        self::assertArrayHasKey('properties', $ulidInterface);
        self::assertArrayHasKey('ulid', $ulidInterface['properties']);
        self::assertSame(['type' => 'string'], $ulidInterface['properties']['ulid']);
    }

    private function createOpenApi(?ArrayObject $schemas): OpenApi
    {
        return new OpenApi(
            new Info('Test', '1.0.0'),
            [],
            new Paths(),
            new Components($schemas)
        );
    }
}
