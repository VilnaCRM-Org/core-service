<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;

final readonly class ApiQueryParametersListener
{
    public function __construct(
        private ApiQueryRequestGuard $guard,
        private ApiQueryParametersParser $parser,
        private ApiQueryParametersSanitizer $sanitizer,
        private ApiQueryAttributesPopulator $populator
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (! $event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (! $this->guard->allows($request)) {
            return;
        }

        $this->populator->populate($request, $this->sanitizer->sanitize(
            $this->parser->parse($request)
        ));
    }
}
