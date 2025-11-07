<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Serializer;

use App\Shared\Application\OpenApi\Serializer\ParameterCleaner;
use App\Tests\Unit\UnitTestCase;

final class ParameterCleanerTest extends UnitTestCase
{
    private ParameterCleaner $cleaner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cleaner = new ParameterCleaner();
    }

    public function testCleanRemovesDisallowedPropertiesFromPathParameters(): void
    {
        $parameters = [
            [
                'name' => 'id',
                'in' => 'path',
                'required' => true,
                'allowEmptyValue' => false,
                'allowReserved' => false,
            ],
        ];

        $result = $this->cleaner->clean($parameters);

        $this->assertArrayHasKey('name', $result[0]);
        $this->assertArrayHasKey('in', $result[0]);
        $this->assertArrayHasKey('required', $result[0]);
        $this->assertArrayNotHasKey('allowEmptyValue', $result[0]);
        $this->assertArrayNotHasKey('allowReserved', $result[0]);
    }

    public function testCleanKeepsPropertiesForQueryParameters(): void
    {
        $parameters = [
            [
                'name' => 'page',
                'in' => 'query',
                'allowEmptyValue' => true,
                'allowReserved' => false,
            ],
        ];

        $result = $this->cleaner->clean($parameters);

        $this->assertArrayHasKey('allowEmptyValue', $result[0]);
        $this->assertArrayHasKey('allowReserved', $result[0]);
    }

    public function testCleanKeepsPropertiesForHeaderParameters(): void
    {
        $parameters = [
            [
                'name' => 'Authorization',
                'in' => 'header',
                'allowEmptyValue' => false,
                'allowReserved' => false,
            ],
        ];

        $result = $this->cleaner->clean($parameters);

        $this->assertArrayHasKey('allowEmptyValue', $result[0]);
        $this->assertArrayHasKey('allowReserved', $result[0]);
    }

    public function testCleanKeepsPropertiesForCookieParameters(): void
    {
        $parameters = [
            [
                'name' => 'session',
                'in' => 'cookie',
                'allowEmptyValue' => true,
            ],
        ];

        $result = $this->cleaner->clean($parameters);

        $this->assertArrayHasKey('allowEmptyValue', $result[0]);
    }

    public function testCleanHandlesMixedParameterTypes(): void
    {
        $parameters = [
            [
                'name' => 'id',
                'in' => 'path',
                'allowEmptyValue' => false,
                'allowReserved' => false,
            ],
            [
                'name' => 'page',
                'in' => 'query',
                'allowEmptyValue' => true,
                'allowReserved' => false,
            ],
        ];

        $result = $this->cleaner->clean($parameters);

        // Path parameter should have properties removed
        $this->assertArrayNotHasKey('allowEmptyValue', $result[0]);
        $this->assertArrayNotHasKey('allowReserved', $result[0]);

        // Query parameter should keep properties
        $this->assertArrayHasKey('allowEmptyValue', $result[1]);
        $this->assertArrayHasKey('allowReserved', $result[1]);
    }

    public function testCleanHandlesNonArrayParameter(): void
    {
        $parameters = [
            'string-parameter',
        ];

        $result = $this->cleaner->clean($parameters);

        $this->assertEquals('string-parameter', $result[0]);
    }

    public function testCleanHandlesParameterWithoutInProperty(): void
    {
        $parameters = [
            [
                'name' => 'test',
                'allowEmptyValue' => false,
            ],
        ];

        $result = $this->cleaner->clean($parameters);

        // Parameter without 'in' property should keep allowEmptyValue
        $this->assertArrayHasKey('allowEmptyValue', $result[0]);
    }

    public function testCleanHandlesEmptyParametersArray(): void
    {
        $parameters = [];

        $result = $this->cleaner->clean($parameters);

        $this->assertEmpty($result);
    }

    public function testCleanRemovesOnlyDisallowedPropertiesFromPathParameter(): void
    {
        $parameters = [
            [
                'name' => 'id',
                'in' => 'path',
                'required' => true,
                'schema' => ['type' => 'string'],
                'allowEmptyValue' => false,
                'allowReserved' => true,
                'description' => 'User ID',
            ],
        ];

        $result = $this->cleaner->clean($parameters);

        // Should keep all properties except allowEmptyValue and allowReserved
        $this->assertArrayHasKey('name', $result[0]);
        $this->assertArrayHasKey('in', $result[0]);
        $this->assertArrayHasKey('required', $result[0]);
        $this->assertArrayHasKey('schema', $result[0]);
        $this->assertArrayHasKey('description', $result[0]);
        $this->assertArrayNotHasKey('allowEmptyValue', $result[0]);
        $this->assertArrayNotHasKey('allowReserved', $result[0]);
    }
}
