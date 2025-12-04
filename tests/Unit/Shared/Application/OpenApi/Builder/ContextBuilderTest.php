<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Builder;

use App\Shared\Application\OpenApi\Builder\ContextBuilder;
use App\Shared\Application\OpenApi\Factory\ParameterSchemaFactory;
use App\Shared\Application\OpenApi\ValueObject\Parameter;
use App\Tests\Unit\UnitTestCase;
use ArrayObject;

final class ContextBuilderTest extends UnitTestCase
{
    private ContextBuilder $contextBuilder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->contextBuilder = new ContextBuilder(new ParameterSchemaFactory());
    }

    public function testConstructorWithCustomParameterSchemaFactory(): void
    {
        $customFactory = $this->createMock(ParameterSchemaFactory::class);
        $customFactory->expects($this->once())
            ->method('create')
            ->willReturn(['type' => 'custom']);

        $builder = new ContextBuilder($customFactory);

        $params = [Parameter::required('test', 'string', 'value')];
        $result = $builder->build($params);

        $properties = $result['application/problem+json']['schema']['properties'];
        $this->assertEquals(['type' => 'custom'], $properties['test']);
    }

    public function testConstructorWithCustomParameterSchemaFactory(): void
    {
        $customFactory = $this->createMock(ParameterSchemaFactory::class);
        $customFactory->expects($this->once())
            ->method('create')
            ->willReturn(['type' => 'custom']);

        $builder = new ContextBuilder($customFactory);

        $params = [Parameter::required('test', 'string', 'value')];
        $result = $builder->build($params);

        $properties = $result['application/problem+json']['schema']['properties'];
        $this->assertEquals(['type' => 'custom'], $properties['test']);
    }

    public function testBuildWithEmptyParams(): void
    {
        $content = $this->contextBuilder->build([]);

        $this->assertEquals(
            new ArrayObject([
                'application/problem+json' => [
                    'example' => new ArrayObject(),
                ],
            ]),
            $content
        );
    }

    public function testBuildWithSimpleParams(): void
    {
        $params = $this->testBuildWithSimpleParamsGetParams();
        $content = $this->contextBuilder->build($params);

        $expectedSchema = $this->buildWithSimpleParamsGetExpectedSchema();
        $expectedExample = [
            'name' => $params[0]->example,
            'age' => $params[1]->example,
        ];

        $this->assertEquals(
            $this->getExpectedResult($expectedSchema, $expectedExample),
            $content
        );
    }

    public function testBuildWithNestedArrays(): void
    {
        $address = [
            'street' => $this->faker->streetName(),
            'city' => $this->faker->city(),
        ];

        $params = [new Parameter('address', 'object', $address)];
        $content = $this->contextBuilder->build($params);

        $expectedSchema = $this->buildWithNestedArraysGetExpectedSchema();
        $expectedExample = ['address' => $address];

        $this->assertEquals(
            $this->getExpectedResult($expectedSchema, $expectedExample),
            $content
        );
    }

    public function testBuildWithMixedRequiredAndOptionalParams(): void
    {
        $params = [
            Parameter::required('name', 'string', 'John'),
            Parameter::optional('nickname', 'string', 'Johnny'),
            Parameter::required('age', 'integer', 25),
        ];

        $content = $this->contextBuilder->build($params);
        $result = $content['application/problem+json'];

        $this->assertArrayHasKey('schema', $result);
        $this->assertArrayHasKey('required', $result['schema']);
        $this->assertEquals(['name', 'age'], $result['schema']['required']);
        $this->assertIsArray($result['schema']['required']);
        // Ensure array_values is used (numeric indices)
        $this->assertArrayHasKey(0, $result['schema']['required']);
        $this->assertArrayHasKey(1, $result['schema']['required']);
    }

    public function testBuildWithNullExamples(): void
    {
        $params = [
            Parameter::required('field1', 'string', 'value1'),
            Parameter::required('field2', 'string', null),
            Parameter::required('field3', 'integer', 123),
        ];

        $content = $this->contextBuilder->build($params);
        $result = $content['application/problem+json'];

        $this->assertArrayHasKey('example', $result);
        // field2 should be filtered out because example is null
        $this->assertEquals(
            ['field1' => 'value1', 'field3' => 123],
            $result['example']
        );
        $this->assertArrayNotHasKey('field2', $result['example']);
    }

    public function testBuildWithAllOptionalParams(): void
    {
        $params = [
            Parameter::optional('opt1', 'string', 'value1'),
            Parameter::optional('opt2', 'integer', 42),
        ];

        $content = $this->contextBuilder->build($params);
        $result = $content['application/problem+json'];

        $this->assertArrayHasKey('schema', $result);
        // When all params are optional, required should be null (filtered out)
        $this->assertArrayNotHasKey('required', $result['schema']);
    }

    public function testBuildWithAllNullExamples(): void
    {
        $params = [
            Parameter::required('field1', 'string', null),
            Parameter::required('field2', 'string', null),
        ];

        $content = $this->contextBuilder->build($params);
        $result = $content['application/problem+json'];

        // When all examples are null, example key should be filtered out
        $this->assertArrayNotHasKey('example', $result);
        $this->assertArrayHasKey('schema', $result);
    }

    public function testBuildFiltersNullSchemaElements(): void
    {
        $params = [Parameter::required('test', 'string', 'value')];

        $content = $this->contextBuilder->build($params);
        $result = $content['application/problem+json'];

        // Verify that array_filter is working on schema
        $this->assertArrayHasKey('schema', $result);
        $schema = $result['schema'];
        $this->assertArrayHasKey('type', $schema);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('required', $schema);
    }

    public function testCollectPropertiesReturnsArray(): void
    {
        $params = [
            Parameter::required('prop1', 'string', 'val1'),
            Parameter::required('prop2', 'integer', 42),
        ];

        $content = $this->contextBuilder->build($params);
        $properties = $content['application/problem+json']['schema']['properties'];

        // Ensure result is an array (from array_combine)
        $this->assertIsArray($properties);
        $this->assertArrayHasKey('prop1', $properties);
        $this->assertArrayHasKey('prop2', $properties);
        $this->assertCount(2, $properties);
    }

    public function testCollectExamplesFiltersNulls(): void
    {
        $params = [
            Parameter::required('with_example', 'string', 'example_value'),
            Parameter::required('without_example', 'string', null),
        ];

        $content = $this->contextBuilder->build($params);
        $example = $content['application/problem+json']['example'];

        // Verify array_filter removes null examples
        $this->assertIsArray($example);
        $this->assertArrayHasKey('with_example', $example);
        $this->assertArrayNotHasKey('without_example', $example);
        $this->assertCount(1, $example);
    }

    /**
     * @return  array<string,string|array<string>>
     */
    private function buildWithSimpleParamsGetExpectedSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                ],
                'age' => [
                    'type' => 'integer',
                ],
            ],
            'required' => ['name', 'age'],
        ];
    }

    /**
     * @return  array<string,string|array<string>>
     */
    private function buildWithNestedArraysGetExpectedSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'address' => [
                    'type' => 'object',
                ],
            ],
            'required' => ['address'],
        ];
    }

    /**
     * @param array<string,string|array<string>> $expectedSchema
     * @param array<string,string|array<string>> $expectedExample
     */
    private function getExpectedResult(
        array $expectedSchema,
        array $expectedExample
    ): ArrayObject {
        return new ArrayObject([
            'application/problem+json' => [
                'schema' => $expectedSchema,
                'example' => $expectedExample,
            ],
        ]);
    }

    /**
     * @return array<Parameter>
     */
    private function testBuildWithSimpleParamsGetParams(): array
    {
        return [
            new Parameter(
                'name',
                'string',
                $this->faker->name()
            ),
            new Parameter(
                'age',
                'integer',
                $this->faker->numberBetween(1, 10)
            ),
        ];
    }
}
