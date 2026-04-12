<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\EventListener;

use App\Shared\Infrastructure\EventListener\ApiNotFoundProblemListener;
use App\Shared\Infrastructure\Factory\ApiProblemJsonResponseFactory;
use App\Tests\Unit\UnitTestCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

final class ApiNotFoundProblemListenerTest extends UnitTestCase
{
    public function testHandlesApiRoutingNotFoundAsProblemJson(): void
    {
        $response = new JsonResponse([], JsonResponse::HTTP_NOT_FOUND);
        $factory = $this->createMock(ApiProblemJsonResponseFactory::class);
        $factory->expects(self::once())
            ->method('createNotFoundResponse')
            ->willReturn($response);

        $listener = new ApiNotFoundProblemListener($factory);
        $event = $this->createExceptionEvent(
            '/api/customers/.%C2%99',
            new NotFoundHttpException('No route found.', new ResourceNotFoundException())
        );

        $listener->onKernelException($event);

        self::assertSame($response, $event->getResponse());
    }

    public function testHandlesApiResourceNotFoundAsProblemJson(): void
    {
        $response = new JsonResponse([], JsonResponse::HTTP_NOT_FOUND);
        $factory = $this->createMock(ApiProblemJsonResponseFactory::class);
        $factory->expects(self::once())
            ->method('createNotFoundResponse')
            ->willReturn($response);

        $listener = new ApiNotFoundProblemListener($factory);
        $event = $this->createExceptionEvent(
            '/api/customers/' . $this->faker->ulid(),
            new NotFoundHttpException('Customer not found.')
        );

        $listener->onKernelException($event);

        self::assertSame($response, $event->getResponse());
    }

    public function testIgnoresNonApiPath(): void
    {
        $factory = $this->createMock(ApiProblemJsonResponseFactory::class);
        $factory->expects(self::never())
            ->method('createNotFoundResponse');

        $listener = new ApiNotFoundProblemListener($factory);
        $event = $this->createExceptionEvent(
            '/favicon.ico',
            new NotFoundHttpException('No route found.', new ResourceNotFoundException())
        );

        $listener->onKernelException($event);

        self::assertNull($event->getResponse());
    }

    public function testIgnoresNonNotFoundExceptions(): void
    {
        $factory = $this->createMock(ApiProblemJsonResponseFactory::class);
        $factory->expects(self::never())
            ->method('createNotFoundResponse');

        $listener = new ApiNotFoundProblemListener($factory);
        $event = new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            Request::create('/api/customers', Request::METHOD_GET),
            HttpKernelInterface::MAIN_REQUEST,
            new RuntimeException('Boom')
        );

        $listener->onKernelException($event);

        self::assertNull($event->getResponse());
    }

    public function testIgnoresSubRequest(): void
    {
        $factory = $this->createMock(ApiProblemJsonResponseFactory::class);
        $factory->expects(self::never())
            ->method('createNotFoundResponse');

        $listener = new ApiNotFoundProblemListener($factory);
        $event = new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            Request::create('/api/customers/' . $this->faker->ulid(), Request::METHOD_GET),
            HttpKernelInterface::SUB_REQUEST,
            new NotFoundHttpException('No route found.', new ResourceNotFoundException())
        );

        $listener->onKernelException($event);

        self::assertNull($event->getResponse());
    }

    private function createExceptionEvent(string $path, NotFoundHttpException $throwable): ExceptionEvent
    {
        return new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            Request::create($path, Request::METHOD_GET),
            HttpKernelInterface::MAIN_REQUEST,
            $throwable
        );
    }
}
