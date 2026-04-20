<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventListener;

use App\Shared\Infrastructure\Factory\ApiProblemJsonResponseFactory;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\PropertyAccess\Exception\InvalidPropertyPathException;

final readonly class ApiInvalidPropertyPathProblemListener
{
    public function __construct(
        private ApiProblemJsonResponseFactory $responseFactory,
        private ApiWriteJsonRequestMatcher $requestMatcher,
    ) {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (! $event->isMainRequest()) {
            return;
        }

        $throwable = $event->getThrowable();

        if (! $throwable instanceof InvalidPropertyPathException) {
            return;
        }

        if (! $this->requestMatcher->matches($event->getRequest())) {
            return;
        }

        $event->setResponse($this->responseFactory->createBadRequestResponse());
    }
}
