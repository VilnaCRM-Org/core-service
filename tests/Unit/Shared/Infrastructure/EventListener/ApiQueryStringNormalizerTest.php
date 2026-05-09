<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\EventListener;

use App\Shared\Infrastructure\EventListener\ApiQueryStringNormalizer;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

final class ApiQueryStringNormalizerTest extends UnitTestCase
{
    public function testNormalizeReplacesQueryBagAndRequestUri(): void
    {
        $request = Request::create(
            '/api/customer_statuses?unsafe%25key=value&page=1',
            Request::METHOD_GET
        );

        (new ApiQueryStringNormalizer())->normalize($request, ['page' => '1']);

        self::assertSame(['page' => '1'], $request->query->all());
        self::assertSame('page=1', $request->server->get('QUERY_STRING'));
        self::assertSame('/api/customer_statuses?page=1', $request->getRequestUri());
    }

    public function testNormalizeIgnoresNonArrayParameters(): void
    {
        $request = Request::create(
            '/api/customer_statuses?page=1',
            Request::METHOD_GET
        );

        (new ApiQueryStringNormalizer())->normalize($request, 'not-array');

        self::assertSame(['page' => '1'], $request->query->all());
        self::assertSame('page=1', $request->server->get('QUERY_STRING'));
        self::assertSame('/api/customer_statuses?page=1', $request->getRequestUri());
    }

    public function testNormalizeUsesPathFromAbsoluteRequestUri(): void
    {
        $request = Request::create(
            '/api/customer_statuses?unsafe%25key=value',
            Request::METHOD_GET
        );
        $request->server->set(
            'REQUEST_URI',
            'https://example.com/api/customer_statuses?unsafe%25key=value'
        );

        (new ApiQueryStringNormalizer())->normalize($request, ['page' => '1']);

        self::assertSame('/api/customer_statuses?page=1', $request->getRequestUri());
    }

    public function testNormalizePrefersAbsoluteRequestUriPathOverRequestPathInfo(): void
    {
        $request = Request::create(
            '/internal?unsafe%25key=value',
            Request::METHOD_GET
        );
        $request->getPathInfo();
        $request->server->set(
            'REQUEST_URI',
            'https://example.com/api/customer_statuses?unsafe%25key=value'
        );

        (new ApiQueryStringNormalizer())->normalize($request, ['page' => '1']);

        self::assertSame('/api/customer_statuses?page=1', $request->getRequestUri());
    }

    public function testNormalizeFallsBackToPathInfoWhenAbsoluteUriHasNoPath(): void
    {
        $request = Request::create(
            '/api/customer_statuses?unsafe%25key=value',
            Request::METHOD_GET
        );
        $request->getPathInfo();
        $request->server->set('REQUEST_URI', 'https://example.com?unsafe%25key=value');

        (new ApiQueryStringNormalizer())->normalize($request, ['page' => '1']);

        self::assertSame('/api/customer_statuses?page=1', $request->getRequestUri());
    }

    public function testNormalizeFallsBackToPathInfoWhenRequestUriIsEmpty(): void
    {
        $request = Request::create(
            '/api/customer_statuses?unsafe%25key=value',
            Request::METHOD_GET
        );
        $request->server->set('REQUEST_URI', '');

        (new ApiQueryStringNormalizer())->normalize($request, ['page' => '1']);

        self::assertSame('/?page=1', $request->getRequestUri());
    }

    public function testNormalizeFallsBackToCachedPathInfoWhenRequestUriIsEmpty(): void
    {
        $request = Request::create(
            '/api/customer_statuses?unsafe%25key=value',
            Request::METHOD_GET
        );
        $request->getPathInfo();
        $request->server->set('REQUEST_URI', '');

        (new ApiQueryStringNormalizer())->normalize($request, ['page' => '1']);

        self::assertSame('/api/customer_statuses?page=1', $request->getRequestUri());
    }

    public function testNormalizeFallsBackToCachedPathInfoWhenRequestUriIsNull(): void
    {
        $request = Request::create(
            '/api/customer_statuses?unsafe%25key=value',
            Request::METHOD_GET
        );
        $request->getPathInfo();
        $request->server->set('REQUEST_URI', null);

        (new ApiQueryStringNormalizer())->normalize($request, ['page' => '1']);

        self::assertSame('/api/customer_statuses?page=1', $request->getRequestUri());
    }

    public function testNormalizeUsesRelativePathWithoutQueryString(): void
    {
        $request = Request::create(
            '/api/customer_statuses?unsafe%25key=value',
            Request::METHOD_GET
        );
        $request->server->set('REQUEST_URI', '/api/customer_statuses');

        (new ApiQueryStringNormalizer())->normalize($request, ['page' => '1']);

        self::assertSame('/api/customer_statuses?page=1', $request->getRequestUri());
    }

    public function testNormalizeUsesRootPathWhenRelativeUriHasOnlyQueryString(): void
    {
        $request = Request::create(
            '/api/customer_statuses?unsafe%25key=value',
            Request::METHOD_GET
        );
        $request->server->set('REQUEST_URI', '?unsafe%25key=value');

        (new ApiQueryStringNormalizer())->normalize($request, ['page' => '1']);

        self::assertSame('/?page=1', $request->getRequestUri());
    }

    public function testNormalizeRemovesQueryStringWhenAllParametersAreDropped(): void
    {
        $request = Request::create(
            '/api/customer_statuses?unsafe%25key=value',
            Request::METHOD_GET
        );

        (new ApiQueryStringNormalizer())->normalize($request, []);

        self::assertSame([], $request->query->all());
        self::assertSame('', $request->server->get('QUERY_STRING'));
        self::assertSame('/api/customer_statuses', $request->getRequestUri());
    }
}
