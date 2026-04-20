<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\EventListener;

use App\Shared\Infrastructure\EventListener\ApiInvalidPropertyPathProblemListener;
use App\Shared\Infrastructure\EventListener\ApiWriteJsonRequestMatcher;
use App\Shared\Infrastructure\Factory\ApiProblemJsonResponseFactory;
use App\Tests\Unit\UnitTestCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\PropertyAccess\Exception\InvalidPropertyPathException;

final class ApiInvalidPropertyPathProblemListenerTest extends UnitTestCase
{
    public function testIgnoresSubRequests(): void
    {
        $factory = $this->createMock(ApiProblemJsonResponseFactory::class);
        $factory->expects(self::never())
            ->method('createBadRequestResponse');

        $listener = new ApiInvalidPropertyPathProblemListener($factory, new ApiWriteJsonRequestMatcher());
        $request = Request::create('/api/customer_types/' . $this->faker->ulid(), Request::METHOD_PATCH);
        $request->headers->set('Content-Type', 'application/merge-patch+json');

        $event = new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::SUB_REQUEST,
            new InvalidPropertyPathException('Could not parse property path ".exe".')
        );

        $listener->onKernelException($event);

        self::assertNull($event->getResponse());
    }

    public function testHandlesApiJsonWriteRequestWithInvalidPropertyPath(): void
    {
        $response = new JsonResponse([], JsonResponse::HTTP_BAD_REQUEST);
        $factory = $this->createMock(ApiProblemJsonResponseFactory::class);
        $factory->expects(self::once())
            ->method('createBadRequestResponse')
            ->willReturn($response);

        $listener = new ApiInvalidPropertyPathProblemListener($factory, new ApiWriteJsonRequestMatcher());
        $request = Request::create('/api/customer_types/' . $this->faker->ulid(), Request::METHOD_PATCH);
        $request->headers->set('Content-Type', 'application/merge-patch+json');

        $event = new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new InvalidPropertyPathException('Could not parse property path ".exe".')
        );

        $listener->onKernelException($event);

        self::assertSame($response, $event->getResponse());
    }

    public function testHandlesApiJsonWriteRequestWithUppercaseContentType(): void
    {
        $response = new JsonResponse([], JsonResponse::HTTP_BAD_REQUEST);
        $factory = $this->createMock(ApiProblemJsonResponseFactory::class);
        $factory->expects(self::once())
            ->method('createBadRequestResponse')
            ->willReturn($response);

        $listener = new ApiInvalidPropertyPathProblemListener($factory, new ApiWriteJsonRequestMatcher());
        $request = Request::create('/api/customer_types/' . $this->faker->ulid(), Request::METHOD_PATCH);
        $request->headers->set('Content-Type', 'Application/Merge-Patch+JSON');

        $event = new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new InvalidPropertyPathException('Could not parse property path ".exe".')
        );

        $listener->onKernelException($event);

        self::assertSame($response, $event->getResponse());
    }

    public function testIgnoresNonJsonRequests(): void
    {
        $factory = $this->createMock(ApiProblemJsonResponseFactory::class);
        $factory->expects(self::never())
            ->method('createBadRequestResponse');

        $listener = new ApiInvalidPropertyPathProblemListener($factory, new ApiWriteJsonRequestMatcher());
        $request = Request::create('/api/customer_types/' . $this->faker->ulid(), Request::METHOD_PATCH);

        $event = new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new InvalidPropertyPathException('Could not parse property path ".exe".')
        );

        $listener->onKernelException($event);

        self::assertNull($event->getResponse());
    }

    public function testIgnoresNonApiPath(): void
    {
        $factory = $this->createMock(ApiProblemJsonResponseFactory::class);
        $factory->expects(self::never())
            ->method('createBadRequestResponse');

        $listener = new ApiInvalidPropertyPathProblemListener($factory, new ApiWriteJsonRequestMatcher());
        $request = Request::create('/favicon.ico', Request::METHOD_PATCH);
        $request->headers->set('Content-Type', 'application/merge-patch+json');

        $event = new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new InvalidPropertyPathException('Could not parse property path ".exe".')
        );

        $listener->onKernelException($event);

        self::assertNull($event->getResponse());
    }

    public function testIgnoresNonPropertyPathExceptions(): void
    {
        $factory = $this->createMock(ApiProblemJsonResponseFactory::class);
        $factory->expects(self::never())
            ->method('createBadRequestResponse');

        $listener = new ApiInvalidPropertyPathProblemListener($factory, new ApiWriteJsonRequestMatcher());
        $request = Request::create('/api/customer_types/' . $this->faker->ulid(), Request::METHOD_PATCH);
        $request->headers->set('Content-Type', 'application/merge-patch+json');

        $event = new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new RuntimeException('Boom')
        );

        $listener->onKernelException($event);

        self::assertNull($event->getResponse());
    }
}
