<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Observability;

use App\Shared\Infrastructure\Observability\ApiEndpointBusinessMetricsSubscriber;
use App\Shared\Infrastructure\Observability\ApiEndpointMetricDimensionsResolver;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class ApiEndpointBusinessMetricsSubscriberTest extends UnitTestCase
{
    public function testSubscribedEvents(): void
    {
        $events = ApiEndpointBusinessMetricsSubscriber::getSubscribedEvents();

        self::assertArrayHasKey('kernel.response', $events);
        self::assertSame('onResponse', $events['kernel.response']);
    }

    public function testEmitsMetricForApiPlatformRequest(): void
    {
        $spy = new BusinessMetricsEmitterSpy();
        $subscriber = new ApiEndpointBusinessMetricsSubscriber($spy, new ApiEndpointMetricDimensionsResolver());

        $request = Request::create('/api/health', 'GET');
        $request->attributes->set('_api_resource_class', 'App\\Internal\\HealthCheck\\Domain\\ValueObject\\HealthCheck');
        $request->attributes->set('_api_operation_name', '_api_/health_get');

        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new Response('', 204)
        );

        $subscriber->onResponse($event);

        $emitted = $spy->emitted();
        self::assertCount(1, $emitted);
        self::assertSame('EndpointInvocations', $emitted[0]['name']);
        self::assertSame(1, $emitted[0]['value']);
        self::assertSame('HealthCheck', $emitted[0]['dimensions']['Endpoint']);
        self::assertSame('_api_/health_get', $emitted[0]['dimensions']['Operation']);
    }

    public function testDoesNotEmitMetricForNonApiRequest(): void
    {
        $spy = new BusinessMetricsEmitterSpy();
        $subscriber = new ApiEndpointBusinessMetricsSubscriber($spy, new ApiEndpointMetricDimensionsResolver());

        $request = Request::create('/favicon.ico', 'GET');

        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new Response('', 200)
        );

        $subscriber->onResponse($event);

        self::assertSame([], $spy->emitted());
    }

    public function testEmitsMetricForGraphqlEndpoint(): void
    {
        $spy = new BusinessMetricsEmitterSpy();
        $subscriber = new ApiEndpointBusinessMetricsSubscriber($spy, new ApiEndpointMetricDimensionsResolver());

        $request = Request::create('/api/graphql', 'POST');

        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new Response('', 200)
        );

        $subscriber->onResponse($event);

        $emitted = $spy->emitted();
        self::assertCount(1, $emitted);
        self::assertSame(1, $emitted[0]['value']);
        self::assertSame('/api/graphql', $emitted[0]['dimensions']['Endpoint']);
        self::assertSame('post', $emitted[0]['dimensions']['Operation']);
    }

    public function testEmitsMetricWithoutOperationNameUsesMethod(): void
    {
        $spy = new BusinessMetricsEmitterSpy();
        $subscriber = new ApiEndpointBusinessMetricsSubscriber($spy, new ApiEndpointMetricDimensionsResolver());

        $request = Request::create('/api/something', 'PATCH');
        $request->attributes->set('_api_resource_class', 'App\\Core\\Customer\\Domain\\Entity\\Customer');

        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new Response('', 200)
        );

        $subscriber->onResponse($event);

        $emitted = $spy->emitted();
        self::assertCount(1, $emitted);
        self::assertSame(1, $emitted[0]['value']);
        self::assertSame('Customer', $emitted[0]['dimensions']['Endpoint']);
        self::assertSame('patch', $emitted[0]['dimensions']['Operation']);
    }

    public function testDoesNotEmitMetricOutsideApiPrefixEvenIfApiOperationAttributePresent(): void
    {
        $spy = new BusinessMetricsEmitterSpy();
        $subscriber = new ApiEndpointBusinessMetricsSubscriber($spy, new ApiEndpointMetricDimensionsResolver());

        $request = Request::create('/something', 'GET');
        $request->attributes->set('_api_operation', new \stdClass());

        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new Response('', 200)
        );

        $subscriber->onResponse($event);

        self::assertSame([], $spy->emitted());
    }

    public function testDoesNotEmitMetricOutsideApiPrefixEvenIfResourceClassPresent(): void
    {
        $spy = new BusinessMetricsEmitterSpy();
        $subscriber = new ApiEndpointBusinessMetricsSubscriber($spy, new ApiEndpointMetricDimensionsResolver());

        $request = Request::create('/something', 'GET');
        $request->attributes->set('_api_resource_class', 'App\\Shared\\Kernel');

        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new Response('', 200)
        );

        $subscriber->onResponse($event);

        self::assertSame([], $spy->emitted());
    }

    public function testDoesNotEmitMetricForSubRequest(): void
    {
        $spy = new BusinessMetricsEmitterSpy();
        $subscriber = new ApiEndpointBusinessMetricsSubscriber($spy, new ApiEndpointMetricDimensionsResolver());

        $request = Request::create('/api/health', 'GET');

        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::SUB_REQUEST,
            new Response('', 204)
        );

        $subscriber->onResponse($event);

        self::assertSame([], $spy->emitted());
    }
}
