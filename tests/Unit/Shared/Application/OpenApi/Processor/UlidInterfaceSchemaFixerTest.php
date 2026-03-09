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
        self::assertSame(['properties' => ['ulid' => ['type' => 'string']]], $resultSchemas['UlidInterface.jsonld-output']);
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

    private function createOpenApi(ArrayObject|null $schemas): OpenApi
    {
        return new OpenApi(
            new Info('Test', '1.0.0'),
            [],
            new Paths(),
            new Components($schemas)
        );
    }
}
