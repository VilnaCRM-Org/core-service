<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Serializer;

use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Cleaner\ArrayValueProcessor;
use App\Shared\Application\OpenApi\Cleaner\DataCleaner;
use App\Shared\Application\OpenApi\Cleaner\EmptyValueChecker;
use App\Shared\Application\OpenApi\Cleaner\ParameterCleaner;
use App\Shared\Application\OpenApi\Cleaner\ValueFilter;
use App\Shared\Application\OpenApi\Serializer\OpenApiNormalizer;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class OpenApiNormalizerTest extends UnitTestCase
{
    private OpenApiNormalizer $normalizer;
    private NormalizerInterface $decorated;

    protected function setUp(): void
    {
        parent::setUp();
        $this->decorated = $this->createMock(NormalizerInterface::class);
        $parameterCleaner = new ParameterCleaner();
        $emptyValueChecker = new EmptyValueChecker();
        $valueFilter = new ValueFilter($emptyValueChecker);
        $arrayProcessor = new ArrayValueProcessor($parameterCleaner, $valueFilter);
        $dataCleaner = new DataCleaner($arrayProcessor, $valueFilter);
        $this->normalizer = new OpenApiNormalizer($this->decorated, $dataCleaner);
    }

    public function testNormalizeRemovesNullValues(): void
    {
        $openApi = $this->createMock(OpenApi::class);
        $decoratedOutput = [
            'title' => 'API',
            'description' => null,
            'version' => '1.0.0',
            'termsOfService' => null,
        ];

        $this->decorated
            ->expects($this->once())
            ->method('normalize')
            ->with($openApi, null, [])
            ->willReturn($decoratedOutput);

        $result = $this->normalizer->normalize($openApi, null, []);

        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('version', $result);
        $this->assertArrayNotHasKey('description', $result);
        $this->assertArrayNotHasKey('termsOfService', $result);
    }

    public function testNormalizeRemovesExtensionProperties(): void
    {
        $openApi = $this->createMock(OpenApi::class);
        $decoratedOutput = [
            'info' => [
                'title' => 'API',
                'extensionProperties' => [],
            ],
        ];

        $this->decorated
            ->expects($this->once())
            ->method('normalize')
            ->willReturn($decoratedOutput);

        $result = $this->normalizer->normalize($openApi);

        $this->assertArrayHasKey('info', $result);
        $this->assertArrayNotHasKey('extensionProperties', $result['info']);
    }

    public function testNormalizeRemovesEmptyComponentSections(): void
    {
        $openApi = $this->createMock(OpenApi::class);
        $decoratedOutput = [
            'components' => [
                'schemas' => ['User' => ['type' => 'object']],
                'responses' => [],
                'parameters' => [],
                'examples' => [],
            ],
        ];

        $this->decorated
            ->expects($this->once())
            ->method('normalize')
            ->willReturn($decoratedOutput);

        $result = $this->normalizer->normalize($openApi);

        $this->assertArrayHasKey('components', $result);
        $this->assertArrayHasKey('schemas', $result['components']);
        $this->assertArrayNotHasKey('responses', $result['components']);
        $this->assertArrayNotHasKey('parameters', $result['components']);
        $this->assertArrayNotHasKey('examples', $result['components']);
    }

    public function testNormalizeRemovesInvalidPathParameterProperties(): void
    {
        $openApi = $this->createMock(OpenApi::class);
        $decoratedOutput = [
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

        $this->decorated
            ->expects($this->once())
            ->method('normalize')
            ->willReturn($decoratedOutput);

        $result = $this->normalizer->normalize($openApi);

        $parameter = $result['paths']['/users/{id}']['get']['parameters'][0];
        $this->assertArrayHasKey('name', $parameter);
        $this->assertArrayHasKey('in', $parameter);
        $this->assertArrayHasKey('required', $parameter);
        $this->assertArrayNotHasKey('allowEmptyValue', $parameter);
        $this->assertArrayNotHasKey('allowReserved', $parameter);
    }

    public function testNormalizeKeepsQueryParameterProperties(): void
    {
        $openApi = $this->createMock(OpenApi::class);
        $decoratedOutput = [
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

        $this->decorated
            ->expects($this->once())
            ->method('normalize')
            ->willReturn($decoratedOutput);

        $result = $this->normalizer->normalize($openApi);

        $parameter = $result['paths']['/users']['get']['parameters'][0];
        $this->assertArrayHasKey('allowEmptyValue', $parameter);
        $this->assertArrayHasKey('allowReserved', $parameter);
    }

    public function testNormalizeReturnsNonArrayUnchanged(): void
    {
        $openApi = $this->createMock(OpenApi::class);

        $this->decorated
            ->expects($this->once())
            ->method('normalize')
            ->willReturn('string-result');

        $result = $this->normalizer->normalize($openApi);

        $this->assertEquals('string-result', $result);
    }

    public function testSupportsNormalization(): void
    {
        $openApi = $this->createMock(OpenApi::class);
        $nonOpenApi = new \stdClass();

        $this->assertTrue($this->normalizer->supportsNormalization($openApi));
        $this->assertFalse($this->normalizer->supportsNormalization($nonOpenApi));
    }

    public function testGetSupportedTypes(): void
    {
        $types = $this->normalizer->getSupportedTypes(null);

        $this->assertArrayHasKey(OpenApi::class, $types);
        $this->assertTrue($types[OpenApi::class]);
    }

    public function testNormalizeWithNestedNullsAndEmptyArrays(): void
    {
        $openApi = $this->createMock(OpenApi::class);
        $decoratedOutput = [
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

        $this->decorated
            ->expects($this->once())
            ->method('normalize')
            ->willReturn($decoratedOutput);

        $result = $this->normalizer->normalize($openApi);

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

    public function testNormalizeWithMixedParametersInSameOperation(): void
    {
        $openApi = $this->createMock(OpenApi::class);
        $decoratedOutput = [
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

        $this->decorated
            ->expects($this->once())
            ->method('normalize')
            ->willReturn($decoratedOutput);

        $result = $this->normalizer->normalize($openApi);

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

    public function testNormalizeWithParameterWithoutInProperty(): void
    {
        $openApi = $this->createMock(OpenApi::class);
        $decoratedOutput = [
            'paths' => [
                '/test' => [
                    'get' => [
                        'parameters' => [
                            [
                                'name' => 'test',
                                // Missing 'in' property
                                'allowEmptyValue' => false,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->decorated
            ->expects($this->once())
            ->method('normalize')
            ->willReturn($decoratedOutput);

        $result = $this->normalizer->normalize($openApi);

        // Parameter without 'in' property should keep allowEmptyValue
        $parameter = $result['paths']['/test']['get']['parameters'][0];
        $this->assertArrayHasKey('allowEmptyValue', $parameter);
    }

    public function testNormalizeWithEmptyArrayWithNumericKey(): void
    {
        $openApi = $this->createMock(OpenApi::class);
        $decoratedOutput = [
            'items' => [
                [],  // Empty array with numeric key (should remain)
                ['value' => 'test'], // Non-empty array
            ],
            'extensionProperties' => [], // Empty array with string key (should be removed)
        ];

        $this->decorated
            ->expects($this->once())
            ->method('normalize')
            ->willReturn($decoratedOutput);

        $result = $this->normalizer->normalize($openApi);

        // Empty array with numeric key should remain
        $this->assertArrayHasKey('items', $result);
        $this->assertCount(2, $result['items']);
        $this->assertEquals([], $result['items'][0]);

        // extensionProperties should be removed
        $this->assertArrayNotHasKey('extensionProperties', $result);
    }

    public function testNormalizeKeepsEmptyArraysWithNonRemovableKeys(): void
    {
        $openApi = $this->createMock(OpenApi::class);
        $decoratedOutput = [
            'customProperty' => [], // Empty array with non-removable key (should remain)
            'responses' => [], // Empty array with removable key (should be removed)
        ];

        $this->decorated
            ->expects($this->once())
            ->method('normalize')
            ->willReturn($decoratedOutput);

        $result = $this->normalizer->normalize($openApi);

        // Custom property with empty array should remain (not in removal list)
        $this->assertArrayHasKey('customProperty', $result);
        $this->assertEquals([], $result['customProperty']);

        // responses should be removed
        $this->assertArrayNotHasKey('responses', $result);
    }

    public function testNormalizeWithHeaderAndCookieParameters(): void
    {
        $openApi = $this->createMock(OpenApi::class);
        $decoratedOutput = [
            'paths' => [
                '/test' => [
                    'get' => [
                        'parameters' => [
                            [
                                'name' => 'Authorization',
                                'in' => 'header',
                                'allowEmptyValue' => false,
                                'allowReserved' => false,
                            ],
                            [
                                'name' => 'session',
                                'in' => 'cookie',
                                'allowEmptyValue' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->decorated
            ->expects($this->once())
            ->method('normalize')
            ->willReturn($decoratedOutput);

        $result = $this->normalizer->normalize($openApi);

        $parameters = $result['paths']['/test']['get']['parameters'];

        // Header and cookie parameters should keep their properties (not path parameters)
        $this->assertArrayHasKey('allowEmptyValue', $parameters[0]);
        $this->assertArrayHasKey('allowReserved', $parameters[0]);
        $this->assertArrayHasKey('allowEmptyValue', $parameters[1]);
    }

    public function testGetSupportedTypesWithFormat(): void
    {
        $types = $this->normalizer->getSupportedTypes('json');

        $this->assertArrayHasKey(OpenApi::class, $types);
        $this->assertTrue($types[OpenApi::class]);
    }

    public function testNormalizeWithNestedArrayBecomingEmptyAfterCleaning(): void
    {
        $openApi = $this->createMock(OpenApi::class);
        $decoratedOutput = [
            'components' => [
                'schemas' => ['User' => ['type' => 'object']],
                // Nested 'responses' that contains only null values, becomes empty after cleaning
                'responses' => [
                    'response1' => null,
                    'response2' => null,
                ],
            ],
        ];

        $this->decorated
            ->expects($this->once())
            ->method('normalize')
            ->willReturn($decoratedOutput);

        $result = $this->normalizer->normalize($openApi);

        // After cleaning, 'responses' should be removed (it becomes empty and is in removal list)
        $this->assertArrayHasKey('components', $result);
        $this->assertArrayHasKey('schemas', $result['components']);
        $this->assertArrayNotHasKey('responses', $result['components']);
    }

    public function testNormalizeConvertsEmptyWebhooksArrayToObject(): void
    {
        $openApi = $this->createMock(OpenApi::class);
        $decoratedOutput = [
            'openapi' => '3.1.0',
            'info' => ['title' => 'API', 'version' => '1.0.0'],
            'webhooks' => [],
        ];

        $this->decorated
            ->expects($this->once())
            ->method('normalize')
            ->willReturn($decoratedOutput);

        $result = $this->normalizer->normalize($openApi);

        $this->assertArrayHasKey('webhooks', $result);
        $this->assertInstanceOf(\stdClass::class, $result['webhooks']);
    }
}
