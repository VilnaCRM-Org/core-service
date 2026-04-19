<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventListener;

use App\Shared\Infrastructure\Factory\ApiProblemJsonResponseFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

final readonly class ApiNotFoundProblemListener
{
    public function __construct(
        private ApiProblemJsonResponseFactory $responseFactory,
    ) {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (! $event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $throwable = $event->getThrowable();

        if (! $this->shouldHandle($request, $throwable)) {
            return;
        }

        $event->setResponse($this->responseFactory->createNotFoundResponse());
    }

    private function shouldHandle(Request $request, Throwable $throwable): bool
    {
        if (! $this->isApiPath($request->getPathInfo())) {
            return false;
        }

        return $throwable instanceof NotFoundHttpException;
    }

    private function isApiPath(string $path): bool
    {
        return $path === '/api' || str_starts_with($path, '/api/');
    }
}
