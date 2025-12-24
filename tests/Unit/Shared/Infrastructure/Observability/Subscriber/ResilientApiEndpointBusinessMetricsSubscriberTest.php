<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Observability\Subscriber;

use App\Shared\Infrastructure\Observability\Subscriber\ApiEndpointBusinessMetricsSubscriber;
use App\Shared\Infrastructure\Observability\Subscriber\ResilientApiEndpointBusinessMetricsSubscriber;
use App\Tests\Unit\UnitTestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class ResilientApiEndpointBusinessMetricsSubscriberTest extends UnitTestCase
{
    public function testSubscribedEventsDelegatesToDecorated(): void
    {
        self::assertSame(
            ApiEndpointBusinessMetricsSubscriber::getSubscribedEvents(),
            ResilientApiEndpointBusinessMetricsSubscriber::getSubscribedEvents()
        );
    }

    public function testCallsDecoratedOnSuccess(): void
    {
        $decorated = $this->createMock(ApiEndpointBusinessMetricsSubscriber::class);
        $decorated->expects(self::once())->method('onResponse');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('error');

        $subscriber = new ResilientApiEndpointBusinessMetricsSubscriber($decorated, $logger);

        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            Request::create('/api/customers', 'GET'),
            HttpKernelInterface::MAIN_REQUEST,
            new Response('', 200)
        );

        $subscriber->onResponse($event);
    }

    public function testCatchesThrowableAndLogsError(): void
    {
        $decorated = $this->createMock(ApiEndpointBusinessMetricsSubscriber::class);
        $decorated->expects(self::once())
            ->method('onResponse')
            ->willThrowException(new \RuntimeException('boom'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('error')
            ->with(
                'Failed to emit endpoint metrics',
                self::callback(static function (array $context): bool {
                    return ($context['path'] ?? null) === '/api/customers'
                        && ($context['method'] ?? null) === 'GET'
                        && ($context['error'] ?? null) === 'boom'
                        && ($context['exception_class'] ?? null) === \RuntimeException::class;
                })
            );

        $subscriber = new ResilientApiEndpointBusinessMetricsSubscriber($decorated, $logger);

        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            Request::create('/api/customers', 'GET'),
            HttpKernelInterface::MAIN_REQUEST,
            new Response('', 200)
        );

        $subscriber->onResponse($event);

        self::assertTrue(true);
    }
}
