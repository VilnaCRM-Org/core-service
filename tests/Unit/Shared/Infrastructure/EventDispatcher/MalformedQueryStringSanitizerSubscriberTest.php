<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\EventDispatcher;

use App\Shared\Infrastructure\EventDispatcher\MalformedQueryStringSanitizerSubscriber;
use App\Shared\Infrastructure\EventDispatcher\QueryStringSanitizer;
use App\Shared\Infrastructure\EventDispatcher\SafeQueryKeyValidator;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class MalformedQueryStringSanitizerSubscriberTest extends UnitTestCase
{
    private QueryStringSanitizer $queryStringSanitizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queryStringSanitizer = new QueryStringSanitizer(new SafeQueryKeyValidator());
    }

    public function testSubscribedEvents(): void
    {
        $events = MalformedQueryStringSanitizerSubscriber::getSubscribedEvents();

        self::assertArrayHasKey('kernel.request', $events);
        self::assertSame(['onRequest', 2048], $events['kernel.request']);
    }

    public function testIgnoresSubRequests(): void
    {
        $subscriber = new MalformedQueryStringSanitizerSubscriber($this->queryStringSanitizer);
        $request = Request::create(
            '/api/customer_statuses?order%5Bulid%5D=&itemsPerPage=12&a%F1%87%8E%80%F3%86%9B%8F%5B=16156'
        );

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::SUB_REQUEST
        );

        $subscriber->onRequest($event);

        self::assertSame(
            'order%5Bulid%5D=&itemsPerPage=12&a%F1%87%8E%80%F3%86%9B%8F%5B=16156',
            $request->server->get('QUERY_STRING')
        );
    }

    public function testIgnoresRequestsWithoutQueryString(): void
    {
        $subscriber = new MalformedQueryStringSanitizerSubscriber($this->queryStringSanitizer);
        $request = Request::create('/api/customer_statuses');

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $subscriber->onRequest($event);

        self::assertSame('', $request->server->get('QUERY_STRING'));
        self::assertSame([], $request->query->all());
    }

    public function testTreatsNullQueryStringAsEmpty(): void
    {
        $subscriber = new MalformedQueryStringSanitizerSubscriber($this->queryStringSanitizer);
        $request = Request::create('/api/customer_statuses');
        $request->server->set('QUERY_STRING', null);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $subscriber->onRequest($event);

        self::assertNull($request->server->get('QUERY_STRING'));
        self::assertSame([], $request->query->all());
    }

    public function testLeavesSafeQueryParametersUntouched(): void
    {
        $subscriber = new MalformedQueryStringSanitizerSubscriber($this->queryStringSanitizer);
        $request = Request::create(
            '/api/customer_statuses?order%5Bulid%5D=desc&itemsPerPage=10&unsupportedParam=value'
        );

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $subscriber->onRequest($event);

        self::assertSame(
            'order%5Bulid%5D=desc&itemsPerPage=10&unsupportedParam=value',
            $request->server->get('QUERY_STRING')
        );
        self::assertSame('desc', $request->query->all()['order']['ulid']);
        self::assertSame('10', $request->query->all()['itemsPerPage']);
        self::assertSame('value', $request->query->all()['unsupportedParam']);
    }

    public function testDoesNotRewriteQueryBagWhenSanitizedQueryStringMatchesOriginal(): void
    {
        $subscriber = new MalformedQueryStringSanitizerSubscriber($this->queryStringSanitizer);
        $request = Request::create('/api/customer_statuses?itemsPerPage=10');
        $request->query->replace(['custom' => 'value']);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $subscriber->onRequest($event);

        self::assertSame(['custom' => 'value'], $request->query->all());
    }

    public function testRemovesMalformedQueryKeysAndRefreshesQueryBag(): void
    {
        $subscriber = new MalformedQueryStringSanitizerSubscriber($this->queryStringSanitizer);
        $request = Request::create(
            '/api/customer_statuses?order%5Bulid%5D=&itemsPerPage=12&a%F1%87%8E%80%F3%86%9B%8F%5B=16156&a%F1%87%8E%80%F3%86%9B%8F%5B=False'
        );

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $subscriber->onRequest($event);

        self::assertSame(
            'order%5Bulid%5D=&itemsPerPage=12',
            $request->server->get('QUERY_STRING')
        );
        self::assertSame(
            ['order' => ['ulid' => ''], 'itemsPerPage' => '12'],
            $request->query->all()
        );
    }

    public function testRemovesUnbalancedBracketKeysEvenWhenTheyAreAscii(): void
    {
        $subscriber = new MalformedQueryStringSanitizerSubscriber($this->queryStringSanitizer);
        $request = Request::create(
            '/api/customer_types?order%5Bulid%5D=desc&broken%5B=value&itemsPerPage=5'
        );

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $subscriber->onRequest($event);

        self::assertSame(
            'order%5Bulid%5D=desc&itemsPerPage=5',
            $request->server->get('QUERY_STRING')
        );
        self::assertArrayNotHasKey('broken_', $request->query->all());
        self::assertSame('5', $request->query->all()['itemsPerPage']);
    }

    public function testAllowsEmptyNestedSegmentsInValidArraySyntax(): void
    {
        $subscriber = new MalformedQueryStringSanitizerSubscriber($this->queryStringSanitizer);
        $request = Request::create(
            '/api/customer_types?filters%5B%5D%5Bvalue%5D=vip&itemsPerPage=5'
        );

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $subscriber->onRequest($event);

        self::assertSame(
            'filters%5B%5D%5Bvalue%5D=vip&itemsPerPage=5',
            $request->server->get('QUERY_STRING')
        );
        self::assertSame('vip', $request->query->all()['filters'][0]['value']);
    }
}
