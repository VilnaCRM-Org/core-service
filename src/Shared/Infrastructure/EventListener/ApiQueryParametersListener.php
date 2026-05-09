<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

final readonly class ApiQueryParametersListener
{
    public function __construct(
        private ApiQueryRequestGuard $guard,
        private ApiQueryParametersParser $parser,
        private ApiQueryParametersSanitizer $sanitizer,
        private ApiQueryStringNormalizer $queryStringNormalizer,
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

        $parameters = $this->effectiveParameters(
            $request,
            $this->sanitizer->sanitize($this->parser->parse($request))
        );

        $this->queryStringNormalizer->normalize($request, $parameters);
        $this->populator->populate($request, $parameters);
    }

    /**
     * @param array<array-key, array|scalar|null> $parameters
     *
     * @return array<array-key, array|scalar|null>
     */
    private function effectiveParameters(Request $request, mixed $parameters): mixed
    {
        $apiQueryParameters = $request->attributes->get('_api_query_parameters');

        if (is_array($apiQueryParameters)) {
            return $apiQueryParameters;
        }

        $apiFilters = $request->attributes->get('_api_filters');

        if (is_array($apiFilters)) {
            return $apiFilters;
        }

        return $parameters;
    }
}
