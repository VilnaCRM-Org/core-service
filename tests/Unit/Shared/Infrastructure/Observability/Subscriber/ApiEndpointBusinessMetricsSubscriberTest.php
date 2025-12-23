<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Observability\Subscriber;

use App\Shared\Infrastructure\Observability\Factory\MetricDimensionsFactory;
use App\Shared\Infrastructure\Observability\Resolver\ApiEndpointMetricDimensionsResolver;
use App\Shared\Infrastructure\Observability\Subscriber\ApiEndpointBusinessMetricsSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Observability\BusinessMetricsEmitterSpy;
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
        $subscriber = new ApiEndpointBusinessMetricsSubscriber(
            $spy,
            new ApiEndpointMetricDimensionsResolver(new MetricDimensionsFactory()),
            new MetricDimensionsFactory()
        );

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

        self::assertSame(1, $spy->count());

        foreach ($spy->emitted() as $metric) {
            self::assertSame('EndpointInvocations', $metric->name());
            self::assertSame(1, $metric->value());
            self::assertSame('HealthCheck', $metric->dimensions()->values()->get('Endpoint'));
            self::assertSame('_api_/health_get', $metric->dimensions()->values()->get('Operation'));
        }
    }

    public function testDoesNotEmitMetricForNonApiRequest(): void
    {
        $spy = new BusinessMetricsEmitterSpy();
        $subscriber = new ApiEndpointBusinessMetricsSubscriber(
            $spy,
            new ApiEndpointMetricDimensionsResolver(new MetricDimensionsFactory()),
            new MetricDimensionsFactory()
        );

        $request = Request::create('/favicon.ico', 'GET');

        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new Response('', 200)
        );

        $subscriber->onResponse($event);

        self::assertSame(0, $spy->count());
    }

    public function testEmitsMetricForGraphqlEndpoint(): void
    {
        $spy = new BusinessMetricsEmitterSpy();
        $subscriber = new ApiEndpointBusinessMetricsSubscriber(
            $spy,
            new ApiEndpointMetricDimensionsResolver(new MetricDimensionsFactory()),
            new MetricDimensionsFactory()
        );

        $request = Request::create('/api/graphql', 'POST');

        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new Response('', 200)
        );

        $subscriber->onResponse($event);

        self::assertSame(1, $spy->count());

        foreach ($spy->emitted() as $metric) {
            self::assertSame(1, $metric->value());
            self::assertSame('/api/graphql', $metric->dimensions()->values()->get('Endpoint'));
            self::assertSame('post', $metric->dimensions()->values()->get('Operation'));
        }
    }

    public function testEmitsMetricWithoutOperationNameUsesMethod(): void
    {
        $spy = new BusinessMetricsEmitterSpy();
        $subscriber = new ApiEndpointBusinessMetricsSubscriber(
            $spy,
            new ApiEndpointMetricDimensionsResolver(new MetricDimensionsFactory()),
            new MetricDimensionsFactory()
        );

        $request = Request::create('/api/something', 'PATCH');
        $request->attributes->set('_api_resource_class', 'App\\Core\\Customer\\Domain\\Entity\\Customer');

        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new Response('', 200)
        );

        $subscriber->onResponse($event);

        self::assertSame(1, $spy->count());

        foreach ($spy->emitted() as $metric) {
            self::assertSame(1, $metric->value());
            self::assertSame('Customer', $metric->dimensions()->values()->get('Endpoint'));
            self::assertSame('patch', $metric->dimensions()->values()->get('Operation'));
        }
    }

    public function testDoesNotEmitMetricOutsideApiPrefixEvenIfApiOperationAttributePresent(): void
    {
        $spy = new BusinessMetricsEmitterSpy();
        $subscriber = new ApiEndpointBusinessMetricsSubscriber(
            $spy,
            new ApiEndpointMetricDimensionsResolver(new MetricDimensionsFactory()),
            new MetricDimensionsFactory()
        );

        $request = Request::create('/something', 'GET');
        $request->attributes->set('_api_operation', new \stdClass());

        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new Response('', 200)
        );

        $subscriber->onResponse($event);

        self::assertSame(0, $spy->count());
    }

    public function testDoesNotEmitMetricOutsideApiPrefixEvenIfResourceClassPresent(): void
    {
        $spy = new BusinessMetricsEmitterSpy();
        $subscriber = new ApiEndpointBusinessMetricsSubscriber(
            $spy,
            new ApiEndpointMetricDimensionsResolver(new MetricDimensionsFactory()),
            new MetricDimensionsFactory()
        );

        $request = Request::create('/something', 'GET');
        $request->attributes->set('_api_resource_class', 'App\\Shared\\Kernel');

        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new Response('', 200)
        );

        $subscriber->onResponse($event);

        self::assertSame(0, $spy->count());
    }

    public function testDoesNotEmitMetricForSubRequest(): void
    {
        $spy = new BusinessMetricsEmitterSpy();
        $subscriber = new ApiEndpointBusinessMetricsSubscriber(
            $spy,
            new ApiEndpointMetricDimensionsResolver(new MetricDimensionsFactory()),
            new MetricDimensionsFactory()
        );

        $request = Request::create('/api/health', 'GET');

        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::SUB_REQUEST,
            new Response('', 204)
        );

        $subscriber->onResponse($event);

        self::assertSame(0, $spy->count());
    }
}
