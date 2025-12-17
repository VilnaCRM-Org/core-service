<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Observability;

use App\Shared\Infrastructure\Observability\ApiEndpointMetricDimensionsResolver;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

final class ApiEndpointMetricDimensionsResolverTest extends UnitTestCase
{
    private ApiEndpointMetricDimensionsResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new ApiEndpointMetricDimensionsResolver();
    }

    public function testDimensionsReturnsEndpointAndOperation(): void
    {
        $request = Request::create('/api/customers', 'GET');
        $request->attributes->set('_api_resource_class', 'App\\Core\\Customer\\Domain\\Entity\\Customer');
        $request->attributes->set('_api_operation_name', '_api_/customers_get_collection');

        $dimensions = $this->resolver->dimensions($request);

        self::assertArrayHasKey('Endpoint', $dimensions);
        self::assertArrayHasKey('Operation', $dimensions);
        self::assertSame('Customer', $dimensions['Endpoint']);
        self::assertSame('_api_/customers_get_collection', $dimensions['Operation']);
    }

    public function testEndpointExtractsClassNameFromResourceClass(): void
    {
        $request = Request::create('/api/customer-types', 'POST');
        $request->attributes->set('_api_resource_class', 'App\\Core\\Customer\\Domain\\Entity\\CustomerType');
        $request->attributes->set('_api_operation_name', '_api_/customer-types_post');

        $dimensions = $this->resolver->dimensions($request);

        self::assertSame('CustomerType', $dimensions['Endpoint']);
    }

    public function testEndpointFallsBackToPathWhenNoResourceClass(): void
    {
        $request = Request::create('/api/graphql', 'POST');

        $dimensions = $this->resolver->dimensions($request);

        self::assertSame('/api/graphql', $dimensions['Endpoint']);
    }

    public function testEndpointFallsBackToPathWhenResourceClassIsEmpty(): void
    {
        $request = Request::create('/api/health', 'GET');
        $request->attributes->set('_api_resource_class', '');

        $dimensions = $this->resolver->dimensions($request);

        self::assertSame('/api/health', $dimensions['Endpoint']);
    }

    public function testOperationUsesOperationNameWhenAvailable(): void
    {
        $request = Request::create('/api/customers/01JCXYZ', 'GET');
        $request->attributes->set('_api_operation_name', '_api_/customers/{ulid}_get');

        $dimensions = $this->resolver->dimensions($request);

        self::assertSame('_api_/customers/{ulid}_get', $dimensions['Operation']);
    }

    public function testOperationFallsBackToHttpMethodWhenNoOperationName(): void
    {
        $request = Request::create('/api/customers', 'PATCH');

        $dimensions = $this->resolver->dimensions($request);

        self::assertSame('patch', $dimensions['Operation']);
    }

    public function testOperationFallsBackToHttpMethodWhenOperationNameIsEmpty(): void
    {
        $request = Request::create('/api/customers', 'DELETE');
        $request->attributes->set('_api_operation_name', '');

        $dimensions = $this->resolver->dimensions($request);

        self::assertSame('delete', $dimensions['Operation']);
    }

    /**
     * @dataProvider httpMethodsProvider
     */
    public function testOperationLowercasesHttpMethod(string $method, string $expected): void
    {
        $request = Request::create('/api/test', $method);

        $dimensions = $this->resolver->dimensions($request);

        self::assertSame($expected, $dimensions['Operation']);
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function httpMethodsProvider(): iterable
    {
        yield 'GET' => ['GET', 'get'];
        yield 'POST' => ['POST', 'post'];
        yield 'PUT' => ['PUT', 'put'];
        yield 'PATCH' => ['PATCH', 'patch'];
        yield 'DELETE' => ['DELETE', 'delete'];
    }

    public function testHandlesNestedResourceClass(): void
    {
        $request = Request::create('/api/customer-statuses', 'GET');
        $request->attributes->set('_api_resource_class', 'App\\Core\\Customer\\Domain\\Entity\\CustomerStatus');
        $request->attributes->set('_api_operation_name', '_api_/customer-statuses_get_collection');

        $dimensions = $this->resolver->dimensions($request);

        self::assertSame('CustomerStatus', $dimensions['Endpoint']);
        self::assertSame('_api_/customer-statuses_get_collection', $dimensions['Operation']);
    }
}
