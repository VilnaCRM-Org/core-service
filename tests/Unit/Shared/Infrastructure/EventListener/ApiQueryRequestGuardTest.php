<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\EventListener;

use App\Shared\Infrastructure\EventListener\ApiQueryRequestGuard;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

final class ApiQueryRequestGuardTest extends UnitTestCase
{
    public function testRejectsRequestWhenRequestUriIsEmpty(): void
    {
        $request = Request::create('/api/customer_statuses?page=1', Request::METHOD_GET);
        $request->server->set('REQUEST_URI', '');

        self::assertFalse((new ApiQueryRequestGuard())->allows($request));
    }

    public function testAllowsApiRequestWhenRequestUriIsAbsolute(): void
    {
        $request = Request::create('/api/customer_statuses?page=1', Request::METHOD_GET);
        $request->server->set(
            'REQUEST_URI',
            'https://example.com/api/customer_statuses?page=1'
        );

        self::assertTrue((new ApiQueryRequestGuard())->allows($request));
    }

    public function testAllowsApiRequestFromAbsoluteUriWhenPathInfoIsNotApi(): void
    {
        $request = Request::create('/internal?page=1', Request::METHOD_GET);
        $request->server->set(
            'REQUEST_URI',
            'https://example.com/api/customer_statuses?page=1'
        );

        self::assertTrue((new ApiQueryRequestGuard())->allows($request));
    }

    public function testAllowsApiRequestWhenRequestUriIsEmptyAfterPathInfoWasResolved(): void
    {
        $request = Request::create('/api/customer_statuses?page=1', Request::METHOD_GET);
        $request->getPathInfo();
        $request->server->set('REQUEST_URI', '');

        self::assertTrue((new ApiQueryRequestGuard())->allows($request));
    }

    public function testAllowsApiRequestWhenRequestUriIsNullAfterPathInfoWasResolved(): void
    {
        $request = Request::create('/api/customer_statuses?page=1', Request::METHOD_GET);
        $request->getPathInfo();
        $request->server->set('REQUEST_URI', null);

        self::assertTrue((new ApiQueryRequestGuard())->allows($request));
    }

    public function testAllowsRelativeApiRequestWithoutQueryInRequestUri(): void
    {
        $request = Request::create('/internal?page=1', Request::METHOD_GET);
        $request->server->set('REQUEST_URI', '/api/customer_statuses');

        self::assertTrue((new ApiQueryRequestGuard())->allows($request));
    }

    public function testRejectsAbsoluteRequestUriWithoutPath(): void
    {
        $request = Request::create('/api/customer_statuses?page=1', Request::METHOD_GET);
        $request->server->set('REQUEST_URI', 'https://example.com?page=1');

        self::assertFalse((new ApiQueryRequestGuard())->allows($request));
    }
}
