<?php

declare(strict_types=1);

namespace App\Tests\Memory\Support;

use App\Tests\Support\Memory\TrackedRequestHolder;
use App\Tests\Support\Memory\TrackedRequestSubscriber;
use LogicException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final class TrackedRequestSubscriberTest extends TestCase
{
    public function testGetSubscribedEventsRegistersKernelRequestHandler(): void
    {
        self::assertSame(
            [KernelEvents::REQUEST => 'onKernelRequest'],
            TrackedRequestSubscriber::getSubscribedEvents()
        );
    }

    public function testOnKernelRequestIgnoresSubRequests(): void
    {
        $holder = new TrackedRequestHolder();
        $subscriber = new TrackedRequestSubscriber($holder);
        $kernel = $this->createStub(HttpKernelInterface::class);

        $subscriber->onKernelRequest(
            new RequestEvent($kernel, Request::create('/memory/sub'), HttpKernelInterface::SUB_REQUEST)
        );

        $this->expectException(LogicException::class);
        $holder->requireTrackedRequest();
    }

    public function testOnKernelRequestTracksMainRequests(): void
    {
        $holder = new TrackedRequestHolder();
        $subscriber = new TrackedRequestSubscriber($holder);
        $kernel = $this->createStub(HttpKernelInterface::class);
        $request = Request::create('/memory/main');

        $subscriber->onKernelRequest(
            new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST)
        );

        self::assertSame($request, $holder->requireTrackedRequest());
    }
}
