<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use App\Shared\Application\OpenApi\Processor\OpenApiFixer;
use App\Tests\Unit\UnitTestCase;
use ArrayObject;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

final class OpenApiFixerTest extends UnitTestCase
{
    private string $tempDir;
    private string $specFile;

    protected function setUp(): void
    {
        parent::setUp();
        $tempFile = tempnam(sys_get_temp_dir(), 'openapi_fixer_test_');
        if ($tempFile === false) {
            throw new RuntimeException('Failed to create a temporary file for OpenApiFixerTest.');
        }

        unlink($tempFile);

        if (! mkdir($tempFile, 0755) && ! is_dir($tempFile)) {
            throw new RuntimeException(sprintf('Failed to create temporary directory "%s".', $tempFile));
        }

        $this->tempDir = $tempFile;
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

    public function testFixUlidRefToTypeAlsoHandlesCustomerStatusSchema(): void
    {
        $spec = [
            'components' => [
                'schemas' => [
                    'CustomerStatus.jsonld-output' => [
                        'type' => 'object',
                        'properties' => [
                            'ulid' => [
                                '$ref' => '#/components/schemas/UlidInterface.jsonld-output',
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
        $this->assertSame(
            ['type' => 'string'],
            $result['components']['schemas']['CustomerStatus.jsonld-output']['properties']['ulid']
        );
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

    public function testSecurityNormalizationDoesNotTouchExampleFieldsNamedSecurity(): void
    {
        $spec = [
            'security' => [],
            'paths' => [
                '/test' => [
                    'get' => [
                        'security' => null,
                        'responses' => [
                            '200' => [
                                'content' => [
                                    'application/json' => [
                                        'example' => [
                                            'security' => null,
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
        $this->assertSame([], $result['security']);
        $this->assertSame([], $result['paths']['/test']['get']['security']);
        $this->assertNull(
            $result['paths']['/test']['get']['responses']['200']['content']['application/json']['example']['security']
        );
    }

    public function testSecurityNormalizationContinuesPastNonArrayPathItems(): void
    {
        $spec = [
            'paths' => [
                '/invalid' => 'not-an-array',
                '/valid' => [
                    'security' => null,
                    'get' => [
                        'security' => null,
                    ],
                ],
            ],
        ];

        $this->writeSpecFile($spec);
        $fixer = new OpenApiFixer($this->specFile);
        $fixer->run();

        $result = $this->readSpecFile();
        $this->assertSame([], $result['paths']['/valid']['security']);
        $this->assertSame([], $result['paths']['/valid']['get']['security']);
    }

    public function testSecurityNormalizationContinuesPastNonArrayOperations(): void
    {
        $spec = [
            'paths' => [
                '/valid' => [
                    'summary' => 'not-an-operation-array',
                    'get' => [
                        'security' => null,
                    ],
                ],
            ],
        ];

        $this->writeSpecFile($spec);
        $fixer = new OpenApiFixer($this->specFile);
        $fixer->run();

        $result = $this->readSpecFile();
        $this->assertSame([], $result['paths']['/valid']['get']['security']);
    }

    public function testSecurityNormalizationPreservesConfiguredSecurityRequirements(): void
    {
        $spec = [
            'security' => [
                ['bearerAuth' => []],
            ],
            'paths' => [
                '/valid' => [
                    'get' => [
                        'security' => [
                            ['apiKeyAuth' => []],
                        ],
                    ],
                ],
            ],
        ];

        $this->writeSpecFile($spec);
        $fixer = new OpenApiFixer($this->specFile);
        $fixer->run();

        $result = $this->readSpecFile();
        $this->assertSame([['bearerAuth' => []]], $result['security']);
        $this->assertSame([['apiKeyAuth' => []]], $result['paths']['/valid']['get']['security']);
    }

    public function testNonEmptyArrayObjectSecurityIsNotNormalized(): void
    {
        $fixer = new OpenApiFixer($this->specFile);
        $node = [
            'security' => new ArrayObject([
                ['apiKeyAuth' => []],
            ]),
        ];

        $method = new \ReflectionMethod($fixer, 'normalizeSecurityValue');
        $method->setAccessible(true);
        $method->invokeArgs($fixer, [&$node]);

        $this->assertInstanceOf(ArrayObject::class, $node['security']);
        $this->assertCount(1, $node['security']);
    }

    public function testEmptyArrayObjectSecurityIsNormalized(): void
    {
        $fixer = new OpenApiFixer($this->specFile);
        $node = [
            'security' => new ArrayObject(),
        ];

        $method = new \ReflectionMethod($fixer, 'normalizeSecurityValue');
        $method->setAccessible(true);
        $method->invokeArgs($fixer, [&$node]);

        $this->assertSame('__OPENAPI_EMPTY_SECURITY__', $node['security']);
    }

    public function testRunThrowsExceptionOnInvalidYaml(): void
    {
        // Write invalid YAML to trigger ParseException in readSpec
        if (file_put_contents($this->specFile, 'invalid: yaml: content:') === false) {
            $this->fail(sprintf('Failed to write invalid YAML fixture: %s', $this->specFile));
        }

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
        $this->assertSame('/errors/500', $result['paths']['/test']['post']['responses']['500']['content']['application/problem+json']['example']['type']);
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
        $example = $result['paths']['/customers']['post']['responses']['422']['content']['application/problem+json']['example'];

        $this->assertSame(422, $example['status']);
        $this->assertSame('/errors/422', $example['type']);
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

    public function testFix422ErrorTypeSkipsNonArrayIndividualResponse(): void
    {
        $spec = [
            'paths' => [
                '/customers' => [
                    'post' => [
                        'responses' => [
                            '422' => 'not-an-array',
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
        $this->assertSame('not-an-array', $result['paths']['/customers']['post']['responses']['422']);
        $this->assertSame('/errors/500', $result['paths']['/customers']['post']['responses']['500']['content']['application/problem+json']['example']['type']);
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
            $files = scandir($path);
            if ($files === false) {
                throw new RuntimeException(sprintf('Failed to read directory "%s".', $path));
            }

            foreach (array_diff($files, ['.', '..']) as $file) {
                $this->recursiveDelete($path . '/' . $file);
            }

            if (! rmdir($path)) {
                throw new RuntimeException(sprintf('Failed to remove directory "%s".', $path));
            }
        } elseif (is_file($path)) {
            if (! unlink($path)) {
                throw new RuntimeException(sprintf('Failed to remove file "%s".', $path));
            }
        }
    }

    private function writeSpecFile(array $spec): void
    {
        $yaml = Yaml::dump($spec, 10, 2, Yaml::DUMP_NUMERIC_KEY_AS_STRING);
        if (file_put_contents($this->specFile, $yaml) === false) {
            throw new RuntimeException(sprintf('Failed to write spec file "%s".', $this->specFile));
        }
    }

    private function readSpecFile(): array
    {
        return Yaml::parseFile($this->specFile);
    }
}
