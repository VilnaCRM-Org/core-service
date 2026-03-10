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

final class UlidInterfaceSchemaFixerMutationCoverageTest extends UnitTestCase
{
    public function testDoesNotDropSchemasWhenRefIsNotUlidInterface(): void
    {
        $schemas = new ArrayObject([
            'Customer.jsonld-output' => [
                'type' => 'object',
                'properties' => [
                    'ulid' => ['$ref' => '#/components/schemas/SomeOtherSchema'],
                ],
            ],
            'SomeOtherSchema' => [
                'type' => 'object',
                'properties' => [
                    'value' => ['type' => 'string'],
                ],
            ],
        ]);

        $fixer = new UlidInterfaceSchemaFixer();
        $result = $fixer->process($this->createOpenApi($schemas));
        $resultSchemas = $result->getComponents()->getSchemas();

        self::assertArrayHasKey('SomeOtherSchema', $resultSchemas);
        self::assertArrayHasKey('UlidInterface.jsonld-output', $resultSchemas);
        self::assertSame(['$ref' => '#/components/schemas/SomeOtherSchema'], $resultSchemas['Customer.jsonld-output']['properties']['ulid']);
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
