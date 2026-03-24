<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use App\Shared\Application\OpenApi\Processor\OpenApiFixer;
use App\Tests\Unit\UnitTestCase;
use ArrayObject;
use Symfony\Component\Yaml\Yaml;

final class OpenApiFixerTest extends UnitTestCase
{
    private string $tempDir;
    private string $specFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/openapi_fixer_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        $this->specFile = $this->tempDir . '/spec.yaml';
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->recursiveDelete($this->tempDir);
    }

    public function testFixExampleTypeToAtTypeWithJsonLdMarker(): void
    {
        $spec = [
            'paths' => [
                '/test' => [
                    'get' => [
                        'responses' => [
                            '200' => [
                                'content' => [
                                    'application/ld+json' => [
                                        'example' => [
                                            '@context' => '/api/contexts/Hello',
                                            '@type' => 'Hello',
                                            'name' => 'test',
                                            'type' => 'string',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->writeSpecFile($spec);
        $fixer = new OpenApiFixer($this->specFile);
        $fixer->run();

        $result = $this->readSpecFile();
        $example = $result['paths']['/test']['get']['responses']['200']['content']['application/ld+json']['example'];

        $this->assertArrayHasKey('@type', $example);
        $this->assertSame('Hello', $example['@type']);
        $this->assertArrayNotHasKey('type', $example);
    }

    public function testFixExampleTypeToAtTypeWithBothTypeAndAtType(): void
    {
        $spec = [
            'paths' => [
                '/test' => [
                    'get' => [
                        'responses' => [
                            '200' => [
                                'content' => [
                                    'application/ld+json' => [
                                        'example' => [
                                            '@context' => '/api/contexts/Collection',
                                            '@type' => 'Collection',
                                            'type' => 'Collection',
                                            'hydra:member' => [],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->writeSpecFile($spec);
        $fixer = new OpenApiFixer($this->specFile);
        $fixer->run();

        $result = $this->readSpecFile();
        $example = $result['paths']['/test']['get']['responses']['200']['content']['application/ld+json']['example'];

        $this->assertArrayHasKey('@type', $example);
        $this->assertSame('Collection', $example['@type']);
        $this->assertArrayNotHasKey('type', $example);
    }

    public function testFixExampleTypeToAtTypeWithAtIdMarkerWithoutAtType(): void
    {
        $spec = [
            'paths' => [
                '/test' => [
                    'get' => [
                        'responses' => [
                            '200' => [
                                'content' => [
                                    'application/ld+json' => [
                                        'example' => [
                                            '@id' => '/api/customers/123',
                                            'name' => 'test',
                                            'type' => 'Customer',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->writeSpecFile($spec);
        $fixer = new OpenApiFixer($this->specFile);
        $fixer->run();

        $result = $this->readSpecFile();
        $example = $result['paths']['/test']['get']['responses']['200']['content']['application/ld+json']['example'];

        $this->assertArrayHasKey('@type', $example);
        $this->assertSame('Customer', $example['@type']);
        $this->assertArrayNotHasKey('type', $example);
    }

    public function testFixExampleTypeToAtTypeWithoutJsonLdMarker(): void
    {
        $spec = [
            'paths' => [
                '/test' => [
                    'get' => [
                        'responses' => [
                            '200' => [
                                'content' => [
                                    'application/json' => [
                                        'example' => [
                                            'name' => 'test',
                                            'type' => 'Customer',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->writeSpecFile($spec);
        $fixer = new OpenApiFixer($this->specFile);
        $fixer->run();

        $result = $this->readSpecFile();
        $example = $result['paths']['/test']['get']['responses']['200']['content']['application/json']['example'];

        $this->assertArrayHasKey('type', $example);
        $this->assertArrayNotHasKey('@type', $example);
    }

    public function testFixExampleTypeToAtTypePreservesAtTypeOnlyExample(): void
    {
        $spec = [
            'paths' => [
                '/test' => [
                    'get' => [
                        'responses' => [
                            '200' => [
                                'content' => [
                                    'application/ld+json' => [
                                        'example' => [
                                            '@type' => 'Customer',
                                            'name' => 'test',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->writeSpecFile($spec);
        $fixer = new OpenApiFixer($this->specFile);
        $fixer->run();

        $result = $this->readSpecFile();
        $example = $result['paths']['/test']['get']['responses']['200']['content']['application/ld+json']['example'];

        $this->assertSame('Customer', $example['@type']);
        $this->assertArrayNotHasKey('type', $example);
    }

    public function testAddUlidPropertyIdempotent(): void
    {
        $spec = [
            'components' => [
                'schemas' => [
                    'UlidInterface.jsonld-output' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
        ];

        $this->writeSpecFile($spec);
        $fixer = new OpenApiFixer($this->specFile);
        $fixer->run();

        $result = $this->readSpecFile();
        $properties = $result['components']['schemas']['UlidInterface.jsonld-output']['properties'];

        $this->assertArrayHasKey('ulid', $properties);
        $this->assertSame(['type' => 'string'], $properties['ulid']);
    }

    public function testAddUlidPropertyAlreadyExists(): void
    {
        $spec = [
            'components' => [
                'schemas' => [
                    'UlidInterface.jsonld-output' => [
                        'type' => 'object',
                        'properties' => [
                            'ulid' => ['type' => 'string', 'description' => 'existing'],
                        ],
                    ],
                ],
            ],
        ];

        $this->writeSpecFile($spec);
        $fixer = new OpenApiFixer($this->specFile);
        $fixer->run();

        $result = $this->readSpecFile();
        $properties = $result['components']['schemas']['UlidInterface.jsonld-output']['properties'];

        $this->assertSame(['type' => 'string', 'description' => 'existing'], $properties['ulid']);
    }

    public function testAddUlidPropertyNoUlidInterfaceSchema(): void
    {
        $spec = [
            'components' => [
                'schemas' => [
                    'Customer.jsonld-output' => [
                        'type' => 'object',
                    ],
                ],
            ],
        ];

        $this->writeSpecFile($spec);
        $fixer = new OpenApiFixer($this->specFile);
        $fixer->run();

        $result = $this->readSpecFile();
        $this->assertArrayNotHasKey('ulid', $result['components']['schemas']['Customer.jsonld-output']['properties'] ?? []);
    }

    public function testAddUlidPropertyNoSchemasKey(): void
    {
        $spec = [
            'components' => [
                'schemas' => 'not-an-array',
            ],
        ];

        $this->writeSpecFile($spec);
        $fixer = new OpenApiFixer($this->specFile);
        $fixer->run();

        $result = $this->readSpecFile();
        $this->assertSame('not-an-array', $result['components']['schemas']);
    }

    public function testAddUlidPropertyWithoutSchemasKeepsComponentsUntouched(): void
    {
        $spec = [
            'components' => [
                'responses' => [
                    'Test' => ['description' => 'ok'],
                ],
            ],
        ];

        $this->writeSpecFile($spec);
        $fixer = new OpenApiFixer($this->specFile);
        $fixer->run();

        $result = $this->readSpecFile();
        $this->assertSame(['description' => 'ok'], $result['components']['responses']['Test']);
        $this->assertArrayNotHasKey('schemas', $result['components']);
    }

    public function testAddUlidPropertyWithNonArrayProperties(): void
    {
        $spec = ['components' => ['schemas' => ['UlidInterface.jsonld-output' => ['type' => 'object', 'properties' => 'not-an-array']]]];

        $this->writeSpecFile($spec);
        $fixer = new OpenApiFixer($this->specFile);
        $fixer->run();

        $result = $this->readSpecFile();
        $properties = $result['components']['schemas']['UlidInterface.jsonld-output']['properties'];
        $this->assertIsArray($properties);
        $this->assertSame(['type' => 'string'], $properties['ulid']);
    }

    public function testAddUlidPropertyWithNullUlidInterfaceSchema(): void
    {
        $spec = ['components' => ['schemas' => ['UlidInterface.jsonld-output' => null]]];

        $this->writeSpecFile($spec);
        $fixer = new OpenApiFixer($this->specFile);
        $fixer->run();

        $result = $this->readSpecFile();
        $this->assertSame(['type' => 'string'], $result['components']['schemas']['UlidInterface.jsonld-output']['properties']['ulid']);
    }

    public function testFixUlidRefToType(): void
    {
        $spec = [
            'components' => [
                'schemas' => [
                    'Customer.jsonld-output' => [
                        'type' => 'object',
                        'properties' => [
                            'ulid' => ['$ref' => '#/components/schemas/UlidInterface'],
                        ],
                    ],
                    'CustomerType.jsonld-output' => [
                        'type' => 'object',
                        'properties' => [
                            'ulid' => ['$ref' => '#/components/schemas/UlidInterface'],
                        ],
                    ],
                ],
            ],
        ];

        $this->writeSpecFile($spec);
        $fixer = new OpenApiFixer($this->specFile);
        $fixer->run();

        $result = $this->readSpecFile();

        $this->assertSame(['type' => 'string'], $result['components']['schemas']['Customer.jsonld-output']['properties']['ulid']);
        $this->assertSame(['type' => 'string'], $result['components']['schemas']['CustomerType.jsonld-output']['properties']['ulid']);
    }

    public function testFixUlidRefToTypeNonUlidRef(): void
    {
        $spec = [
            'components' => [
                'schemas' => [
                    'Customer.jsonld-output' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['$ref' => '#/components/schemas/UuidInterface'],
                        ],
                    ],
                ],
            ],
        ];

        $this->writeSpecFile($spec);
        $fixer = new OpenApiFixer($this->specFile);
        $fixer->run();

        $result = $this->readSpecFile();

        // Should not change non-UlidInterface refs
        $this->assertSame(['$ref' => '#/components/schemas/UuidInterface'], $result['components']['schemas']['Customer.jsonld-output']['properties']['id']);
    }

    public function testFixUlidRefToTypeSkipsInvalidSchemaAndContinues(): void
    {
        $spec = [
            'components' => [
                'schemas' => [
                    'Customer.jsonld-output' => [
                        'type' => 'object',
                        'properties' => 'not-an-array',
                    ],
                    'CustomerType.jsonld-output' => [
                        'type' => 'object',
                        'properties' => [
                            'ulid' => ['$ref' => '#/components/schemas/UlidInterface'],
                        ],
                    ],
                ],
            ],
        ];

        $this->writeSpecFile($spec);
        $fixer = new OpenApiFixer($this->specFile);
        $fixer->run();

        $result = $this->readSpecFile();
        $this->assertSame('not-an-array', $result['components']['schemas']['Customer.jsonld-output']['properties']);
        $this->assertSame(['type' => 'string'], $result['components']['schemas']['CustomerType.jsonld-output']['properties']['ulid']);
    }

    public function testFixUlidRefToTypeDoesNotChangeNonStringRef(): void
    {
        $spec = [
            'components' => [
                'schemas' => [
                    'Customer.jsonld-output' => [
                        'type' => 'object',
                        'properties' => [
                            'ulid' => ['$ref' => ['UlidInterface']],
                        ],
                    ],
                ],
            ],
        ];

        $this->writeSpecFile($spec);
        $fixer = new OpenApiFixer($this->specFile);
        $fixer->run();

        $result = $this->readSpecFile();
        $this->assertSame(['$ref' => ['UlidInterface']], $result['components']['schemas']['Customer.jsonld-output']['properties']['ulid']);
    }

    public function testFixUlidRefToTypeSkipsMissingSchemaAndContinues(): void
    {
        $spec = [
            'components' => [
                'schemas' => [
                    'CustomerType.jsonld-output' => [
                        'type' => 'object',
                        'properties' => [
                            'ulid' => ['$ref' => '#/components/schemas/UlidInterface'],
                        ],
                    ],
                ],
            ],
        ];

        $this->writeSpecFile($spec);
        $fixer = new OpenApiFixer($this->specFile);
        $fixer->run();

        $result = $this->readSpecFile();
        $this->assertSame(['type' => 'string'], $result['components']['schemas']['CustomerType.jsonld-output']['properties']['ulid']);
    }

    public function testFix422ErrorType(): void
    {
        $spec = [
            'paths' => [
                '/customers' => [
                    'post' => [
                        'responses' => [
                            '422' => [
                                'content' => [
                                    'application/problem+json' => [
                                        'example' => [
                                            'status' => 422,
                                            'type' => '/errors/500',
                                            'detail' => 'Validation failed',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->writeSpecFile($spec);
        $fixer = new OpenApiFixer($this->specFile);
        $fixer->run();

        $result = $this->readSpecFile();
        $example = $result['paths']['/customers']['post']['responses']['422']['content']['application/problem+json']['example'];

        $this->assertSame('/errors/422', $example['type']);
    }

    public function testFix422ErrorTypeNon422Status(): void
    {
        $spec = [
            'paths' => [
                '/customers' => [
                    'post' => [
                        'responses' => [
                            '500' => [
                                'content' => [
                                    'application/problem+json' => [
                                        'example' => [
                                            'status' => 500,
                                            'type' => '/errors/500',
                                            'detail' => 'Server error',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->writeSpecFile($spec);
        $fixer = new OpenApiFixer($this->specFile);
        $fixer->run();

        $result = $this->readSpecFile();
        $example = $result['paths']['/customers']['post']['responses']['500']['content']['application/problem+json']['example'];

        // Should not change non-422 error types
        $this->assertSame('/errors/500', $example['type']);
    }

    public function testFix204Responses(): void
    {
        $spec = [
            'paths' => [
                '/customers/{id}' => [
                    'delete' => [
                        'responses' => [
                            '204' => [
                                'description' => 'No Content',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['type' => 'object'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->writeSpecFile($spec);
        $fixer = new OpenApiFixer($this->specFile);
        $fixer->run();

        $result = $this->readSpecFile();
        $response = $result['paths']['/customers/{id}']['delete']['responses']['204'];

        $this->assertArrayNotHasKey('content', $response);
    }

    public function testFix204ResponsesNumericKey(): void
    {
        $spec = [
            'paths' => [
                '/customers/{id}' => [
                    'delete' => [
                        'responses' => [
                            204 => [
                                'description' => 'No Content',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['type' => 'object'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->writeSpecFile($spec);
        $fixer = new OpenApiFixer($this->specFile);
        $fixer->run();

        $result = $this->readSpecFile();
        $response = $result['paths']['/customers/{id}']['delete']['responses'][204];

        $this->assertArrayNotHasKey('content', $response);
    }

    public function testNoComponents(): void
    {
        $spec = [
            'paths' => [
                '/test' => [
                    'get' => [
                        'responses' => [
                            '200' => [
                                'description' => 'OK',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->writeSpecFile($spec);
        $fixer = new OpenApiFixer($this->specFile);
        $fixer->run();

        $result = $this->readSpecFile();
        // Should not throw and should complete successfully
        $this->assertArrayHasKey('paths', $result);
    }

    public function testNoPaths(): void
    {
        $spec = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'Test API',
                'version' => '1.0.0',
            ],
        ];

        $this->writeSpecFile($spec);
        $fixer = new OpenApiFixer($this->specFile);
        $fixer->run();

        $result = $this->readSpecFile();
        $this->assertArrayHasKey('openapi', $result);
    }

    public function testSecurityEmptyObjectNormalization(): void
    {
        $spec = [
            'security' => new ArrayObject([]),
            'paths' => [],
        ];

        $this->writeSpecFile($spec);
        $fixer = new OpenApiFixer($this->specFile);
        $fixer->run();

        // The YAML should have security as an empty array
        $content = file_get_contents($this->specFile);
        $this->assertStringContainsString('security: []', $content);
    }

    public function testRunThrowsExceptionOnInvalidYaml(): void
    {
        // Write invalid YAML to trigger ParseException in readSpec
        file_put_contents($this->specFile, 'invalid: yaml: content:');

        $fixer = new OpenApiFixer($this->specFile);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to parse OpenAPI spec');

        $fixer->run();
    }

    public function testFix422ErrorTypeWithNonArrayPath(): void
    {
        // Test case: path item is not an array (edge case) - should skip gracefully
        $spec = [
            'paths' => [
                '/test' => 'not-an-array', // This should be skipped
                '/other' => [
                    'post' => [
                        'responses' => [
                            '422' => [
                                'content' => [
                                    'application/problem+json' => [
                                        'example' => [
                                            'status' => 422,
                                            'type' => '/errors/500',
                                            'detail' => 'Validation failed',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->writeSpecFile($spec);
        $fixer = new OpenApiFixer($this->specFile);
        $fixer->run();

        // Should not throw and should process the valid path
        $result = $this->readSpecFile();
        $this->assertSame('/errors/422', $result['paths']['/other']['post']['responses']['422']['content']['application/problem+json']['example']['type']);
    }

    public function testFix422ErrorTypeWithNonArrayMethod(): void
    {
        // Test case: method is not an array - should skip gracefully
        $spec = [
            'paths' => [
                '/test' => [
                    'get' => 'not-an-array', // This should be skipped
                    'post' => [
                        'responses' => [
                            '422' => [
                                'content' => [
                                    'application/problem+json' => [
                                        'example' => [
                                            'status' => 422,
                                            'type' => '/errors/500',
                                            'detail' => 'Validation failed',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->writeSpecFile($spec);
        $fixer = new OpenApiFixer($this->specFile);
        $fixer->run();

        // Should not throw and should process the valid method
        $result = $this->readSpecFile();
        $this->assertSame('/errors/422', $result['paths']['/test']['post']['responses']['422']['content']['application/problem+json']['example']['type']);
    }

    public function testFix422ErrorTypeWithNonArrayResponses(): void
    {
        $spec = [
            'paths' => [
                '/test' => [
                    'post' => [
                        'responses' => 'not-an-array',
                    ],
                    'put' => [
                        'responses' => [
                            '422' => [
                                'content' => [
                                    'application/problem+json' => [
                                        'example' => [
                                            'status' => 422,
                                            'type' => '/errors/500',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->writeSpecFile($spec);
        $fixer = new OpenApiFixer($this->specFile);
        $fixer->run();

        $result = $this->readSpecFile();
        $this->assertSame('not-an-array', $result['paths']['/test']['post']['responses']);
        $this->assertSame('/errors/422', $result['paths']['/test']['put']['responses']['422']['content']['application/problem+json']['example']['type']);
    }

    public function testFix422ErrorTypeLeavesResponseWithoutContentUnchanged(): void
    {
        $spec = [
            'paths' => [
                '/test' => [
                    'post' => [
                        'responses' => [
                            '422' => [
                                'description' => 'Validation failed',
                            ],
                            '500' => [
                                'content' => [
                                    'application/problem+json' => [
                                        'example' => [
                                            'status' => 422,
                                            'type' => '/errors/500',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->writeSpecFile($spec);
        $fixer = new OpenApiFixer($this->specFile);
        $fixer->run();

        $result = $this->readSpecFile();
        $this->assertSame('Validation failed', $result['paths']['/test']['post']['responses']['422']['description']);
        $this->assertSame('/errors/422', $result['paths']['/test']['post']['responses']['500']['content']['application/problem+json']['example']['type']);
    }

    public function testFix422ErrorTypeCastsStringStatusToInteger(): void
    {
        $spec = [
            'paths' => [
                '/customers' => [
                    'post' => [
                        'responses' => [
                            '422' => [
                                'content' => [
                                    'application/problem+json' => [
                                        'example' => [
                                            'status' => '422',
                                            'type' => '/errors/500',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->writeSpecFile($spec);
        $fixer = new OpenApiFixer($this->specFile);
        $fixer->run();

        $result = $this->readSpecFile();
        $this->assertSame('/errors/422', $result['paths']['/customers']['post']['responses']['422']['content']['application/problem+json']['example']['type']);
    }

    public function testFix422ErrorTypeDoesNotChangeMissingStatusExample(): void
    {
        $spec = [
            'paths' => [
                '/customers' => [
                    'post' => [
                        'responses' => [
                            '422' => [
                                'content' => [
                                    'application/problem+json' => [
                                        'example' => [
                                            'type' => '/errors/500',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->writeSpecFile($spec);
        $fixer = new OpenApiFixer($this->specFile);
        $fixer->run();

        $result = $this->readSpecFile();
        $this->assertSame('/errors/500', $result['paths']['/customers']['post']['responses']['422']['content']['application/problem+json']['example']['type']);
    }

    public function testFix204ResponsesWithNonArrayResponses(): void
    {
        $spec = [
            'paths' => [
                '/test' => [
                    'delete' => [
                        'responses' => 'not-an-array',
                    ],
                    'post' => [
                        'responses' => [
                            '204' => [
                                'description' => 'No Content',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['type' => 'object'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->writeSpecFile($spec);
        $fixer = new OpenApiFixer($this->specFile);
        $fixer->run();

        $result = $this->readSpecFile();
        $this->assertSame('not-an-array', $result['paths']['/test']['delete']['responses']);
        $this->assertArrayNotHasKey('content', $result['paths']['/test']['post']['responses']['204']);
    }

    public function testFix204ResponsesSkipsInvalidMethodAndContinues(): void
    {
        $spec = [
            'paths' => [
                '/test' => [
                    'delete' => [
                        'responses' => null,
                    ],
                    'post' => [
                        'responses' => [
                            '204' => [
                                'description' => 'No Content',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['type' => 'object'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->writeSpecFile($spec);
        $fixer = new OpenApiFixer($this->specFile);
        $fixer->run();

        $result = $this->readSpecFile();
        $this->assertNull($result['paths']['/test']['delete']['responses']);
        $this->assertArrayNotHasKey('content', $result['paths']['/test']['post']['responses']['204']);
    }

    public function testFix204ResponsesDoesNotRemoveContentFromNon204Status(): void
    {
        $spec = [
            'paths' => [
                '/customers/{id}' => [
                    'delete' => [
                        'responses' => [
                            '201' => [
                                'description' => 'Created',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['type' => 'object'],
                                    ],
                                ],
                            ],
                            '204' => [
                                'description' => 'No Content',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['type' => 'object'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->writeSpecFile($spec);
        $fixer = new OpenApiFixer($this->specFile);
        $fixer->run();

        $result = $this->readSpecFile();
        $this->assertArrayHasKey('content', $result['paths']['/customers/{id}']['delete']['responses']['201']);
        $this->assertArrayNotHasKey('content', $result['paths']['/customers/{id}']['delete']['responses']['204']);
    }

    private function recursiveDelete(string $path): void
    {
        if (is_dir($path)) {
            $files = glob($path . '/*') ?: [];
            array_map(fn ($file) => $this->recursiveDelete($file), $files);
            rmdir($path);
        } elseif (is_file($path)) {
            unlink($path);
        }
    }

    private function writeSpecFile(array $spec): void
    {
        $yaml = Yaml::dump($spec, 10, 2, Yaml::DUMP_NUMERIC_KEY_AS_STRING);
        file_put_contents($this->specFile, $yaml);
    }

    private function readSpecFile(): array
    {
        return Yaml::parseFile($this->specFile);
    }
}
