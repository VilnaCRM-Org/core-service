<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Cleaner;

use App\Shared\Application\OpenApi\Cleaner\ArrayValueProcessor;
use App\Shared\Application\OpenApi\Cleaner\DataCleaner;
use App\Shared\Application\OpenApi\Cleaner\ParameterCleaner;
use App\Shared\Application\OpenApi\Cleaner\ValueFilter;
use App\Shared\Application\OpenApi\Serializer\EmptyValueChecker;
use App\Tests\Unit\UnitTestCase;

final class DataCleanerTest extends UnitTestCase
{
    private DataCleaner $cleaner;

    protected function setUp(): void
    {
        parent::setUp();
        $parameterCleaner = new ParameterCleaner();
        $emptyValueChecker = new EmptyValueChecker();
        $valueFilter = new ValueFilter($emptyValueChecker);
        $arrayProcessor = new ArrayValueProcessor($parameterCleaner, $valueFilter);
        $this->cleaner = new DataCleaner($arrayProcessor, $valueFilter);
    }

    public function testCleanRemovesNullValues(): void
    {
        $data = [
            'title' => 'API',
            'description' => null,
            'version' => '1.0.0',
            'termsOfService' => null,
        ];

        $result = $this->cleaner->clean($data);

        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('version', $result);
        $this->assertArrayNotHasKey('description', $result);
        $this->assertArrayNotHasKey('termsOfService', $result);
    }

    public function testCleanRemovesExtensionProperties(): void
    {
        $data = [
            'info' => [
                'title' => 'API',
                'extensionProperties' => [],
            ],
        ];

        $result = $this->cleaner->clean($data);

        $this->assertArrayHasKey('info', $result);
        $this->assertArrayNotHasKey('extensionProperties', $result['info']);
    }

    public function testCleanRemovesEmptyComponentSections(): void
    {
        $data = [
            'components' => [
                'schemas' => ['User' => ['type' => 'object']],
                'responses' => [],
                'parameters' => [],
                'examples' => [],
            ],
        ];

        $result = $this->cleaner->clean($data);

        $this->assertArrayHasKey('components', $result);
        $this->assertArrayHasKey('schemas', $result['components']);
        $this->assertArrayNotHasKey('responses', $result['components']);
        $this->assertArrayNotHasKey('parameters', $result['components']);
        $this->assertArrayNotHasKey('examples', $result['components']);
    }

    public function testCleanRemovesInvalidPathParameterProperties(): void
    {
        $data = [
            'paths' => [
                '/users/{id}' => [
                    'get' => [
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'required' => true,
                                'allowEmptyValue' => false,
                                'allowReserved' => false,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->cleaner->clean($data);

        $parameter = $result['paths']['/users/{id}']['get']['parameters'][0];
        $this->assertArrayHasKey('name', $parameter);
        $this->assertArrayHasKey('in', $parameter);
        $this->assertArrayHasKey('required', $parameter);
        $this->assertArrayNotHasKey('allowEmptyValue', $parameter);
        $this->assertArrayNotHasKey('allowReserved', $parameter);
    }

    public function testCleanKeepsQueryParameterProperties(): void
    {
        $data = [
            'paths' => [
                '/users' => [
                    'get' => [
                        'parameters' => [
                            [
                                'name' => 'page',
                                'in' => 'query',
                                'allowEmptyValue' => true,
                                'allowReserved' => false,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->cleaner->clean($data);

        $parameter = $result['paths']['/users']['get']['parameters'][0];
        $this->assertArrayHasKey('allowEmptyValue', $parameter);
        $this->assertArrayHasKey('allowReserved', $parameter);
    }

    public function testCleanHandlesNestedNullsAndEmptyArrays(): void
    {
        $data = [
            'info' => [
                'title' => 'API',
                'contact' => [
                    'name' => 'Support',
                    'email' => null,
                    'extensionProperties' => [],
                ],
                'extensionProperties' => [],
            ],
            'components' => [
                'schemas' => ['User' => ['type' => 'object']],
                'responses' => [],
                'headers' => [],
            ],
        ];

        $result = $this->cleaner->clean($data);

        // Check nested cleaning
        $this->assertArrayHasKey('contact', $result['info']);
        $this->assertArrayNotHasKey('email', $result['info']['contact']);
        $this->assertArrayNotHasKey('extensionProperties', $result['info']['contact']);
        $this->assertArrayNotHasKey('extensionProperties', $result['info']);

        // Check components cleaned
        $this->assertArrayHasKey('schemas', $result['components']);
        $this->assertArrayNotHasKey('responses', $result['components']);
        $this->assertArrayNotHasKey('headers', $result['components']);
    }

    public function testCleanHandlesEmptyArrayWithNumericKey(): void
    {
        $data = [
            'items' => [
                [],  // Empty array with numeric key (should remain)
                ['value' => 'test'], // Non-empty array
            ],
            'extensionProperties' => [], // Empty array with string key (should be removed)
        ];

        $result = $this->cleaner->clean($data);

        // Empty array with numeric key should remain
        $this->assertArrayHasKey('items', $result);
        $this->assertCount(2, $result['items']);
        $this->assertEquals([], $result['items'][0]);

        // extensionProperties should be removed
        $this->assertArrayNotHasKey('extensionProperties', $result);
    }

    public function testCleanKeepsEmptyArraysWithNonRemovableKeys(): void
    {
        $data = [
            'customProperty' => [], // Empty array with non-removable key (should remain)
            'responses' => [], // Empty array with removable key (should be removed)
        ];

        $result = $this->cleaner->clean($data);

        // Custom property with empty array should remain (not in removal list)
        $this->assertArrayHasKey('customProperty', $result);
        $this->assertEquals([], $result['customProperty']);

        // responses should be removed
        $this->assertArrayNotHasKey('responses', $result);
    }

    public function testCleanHandlesMixedParametersInSameOperation(): void
    {
        $data = [
            'paths' => [
                '/users/{id}' => [
                    'get' => [
                        'parameters' => [
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
                            'string-parameter', // Non-array parameter (edge case)
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->cleaner->clean($data);

        $parameters = $result['paths']['/users/{id}']['get']['parameters'];

        // Path parameter should have properties removed
        $this->assertArrayNotHasKey('allowEmptyValue', $parameters[0]);
        $this->assertArrayNotHasKey('allowReserved', $parameters[0]);

        // Query parameter should keep properties
        $this->assertArrayHasKey('allowEmptyValue', $parameters[1]);
        $this->assertArrayHasKey('allowReserved', $parameters[1]);

        // String parameter should remain unchanged
        $this->assertEquals('string-parameter', $parameters[2]);
    }

    public function testCleanHandlesNestedArrayBecomingEmptyAfterCleaning(): void
    {
        $data = [
            'components' => [
                'schemas' => ['User' => ['type' => 'object']],
                // Nested 'responses' that contains only null values, becomes empty after cleaning
                'responses' => [
                    'response1' => null,
                    'response2' => null,
                ],
            ],
        ];

        $result = $this->cleaner->clean($data);

        // After cleaning, 'responses' should be removed (it becomes empty and is in removal list)
        $this->assertArrayHasKey('components', $result);
        $this->assertArrayHasKey('schemas', $result['components']);
        $this->assertArrayNotHasKey('responses', $result['components']);
    }

    public function testCleanKeepsNonArrayValues(): void
    {
        $data = [
            'string' => 'value',
            'number' => 42,
            'boolean' => true,
            'float' => 3.14,
        ];

        $result = $this->cleaner->clean($data);

        $this->assertEquals('value', $result['string']);
        $this->assertEquals(42, $result['number']);
        $this->assertTrue($result['boolean']);
        $this->assertEquals(3.14, $result['float']);
    }

    public function testCleanHandlesArrayObjectValues(): void
    {
        $arrayObject = new \ArrayObject(['key1' => 'value1', 'key2' => null]);
        $data = [
            'nested' => $arrayObject,
        ];

        $result = $this->cleaner->clean($data);

        // ArrayObject should be converted to array and null values should be removed
        $this->assertArrayHasKey('nested', $result);
        $this->assertIsArray($result['nested']);
        $this->assertArrayHasKey('key1', $result['nested']);
        $this->assertArrayNotHasKey('key2', $result['nested']);
        $this->assertEquals('value1', $result['nested']['key1']);
    }
}
