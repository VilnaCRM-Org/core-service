<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\EventListener;

use App\Shared\Infrastructure\EventListener\ApiQueryParametersListener;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class ApiQueryParametersListenerTest extends UnitTestCase
{
    public function testPopulatesApiQueryParametersFromSymfonyRequestBag(): void
    {
        $request = Request::create(
            '/api/customer_statuses?order%5Bulid%5D=asc&itemsPerPage=3&value=Active',
            Request::METHOD_GET
        );
        $event = $this->createRequestEvent($request);

        $listener = new ApiQueryParametersListener();
        $listener($event);

        self::assertSame(
            [
                'order' => ['ulid' => 'asc'],
                'itemsPerPage' => '3',
                'value' => 'Active',
            ],
            $request->attributes->get('_api_query_parameters')
        );
        self::assertSame(
            [
                'order' => ['ulid' => 'asc'],
                'itemsPerPage' => '3',
                'value' => 'Active',
            ],
            $request->attributes->get('_api_filters')
        );
    }

    public function testDoesNotOverwriteExistingApiQueryParameters(): void
    {
        $request = Request::create('/api/customer_statuses?page=2', Request::METHOD_GET);
        $request->attributes->set('_api_query_parameters', ['page' => '99']);
        $event = $this->createRequestEvent($request);

        $listener = new ApiQueryParametersListener();
        $listener($event);

        self::assertSame(['page' => '99'], $request->attributes->get('_api_query_parameters'));
        self::assertSame(['page' => '2'], $request->attributes->get('_api_filters'));
    }

    public function testRemovesMalformedTopLevelQueryKeys(): void
    {
        $request = Request::create(
            '/api/customer_statuses?%C3%A9%C2%A3%C2%87=bad&order%5Bulid%5D=asc&value=Active',
            Request::METHOD_GET
        );
        $event = $this->createRequestEvent($request);

        $listener = new ApiQueryParametersListener();
        $listener($event);

        self::assertSame(
            [
                'order' => ['ulid' => 'asc'],
                'value' => 'Active',
            ],
            $request->attributes->get('_api_query_parameters')
        );
    }

    public function testIgnoresNonApiRequests(): void
    {
        $request = Request::create('/health?page=2', Request::METHOD_GET);
        $event = $this->createRequestEvent($request);

        $listener = new ApiQueryParametersListener();
        $listener($event);

        self::assertFalse($request->attributes->has('_api_query_parameters'));
    }

    public function testIgnoresApiRequestsWithoutQueryParameters(): void
    {
        $request = Request::create('/api/customer_statuses', Request::METHOD_GET);
        $event = $this->createRequestEvent($request);

        $listener = new ApiQueryParametersListener();
        $listener($event);

        self::assertFalse($request->attributes->has('_api_query_parameters'));
    }

    public function testIgnoresSubRequests(): void
    {
        $request = Request::create('/api/customer_statuses?page=2', Request::METHOD_GET);
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::SUB_REQUEST
        );

        $listener = new ApiQueryParametersListener();
        $listener($event);

        self::assertFalse($request->attributes->has('_api_query_parameters'));
    }

    private function createRequestEvent(Request $request): RequestEvent
    {
        return new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );
    }
}
