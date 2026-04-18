<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventListener;

use Symfony\Component\HttpFoundation\Request;

final class ApiQueryAttributesPopulator
{
    /**
     * @param array<array-key, array|scalar|null>|scalar|null $parameters
     */
    public function populate(Request $request, $parameters): void
    {
        if (! is_array($parameters)) {
            return;
        }

        $hasQueryParameters = $request->attributes->has('_api_query_parameters');
        $hasFilters = $request->attributes->has('_api_filters');

        if ($hasQueryParameters) {
            $this->copyAttribute($request, '_api_query_parameters', '_api_filters');
            return;
        }

        if ($hasFilters) {
            $this->copyAttribute($request, '_api_filters', '_api_query_parameters');
            return;
        }

        $this->populateMissingAttributes($request, $parameters);
    }

    private function copyAttribute(Request $request, string $source, string $target): void
    {
        $request->attributes->set($target, $request->attributes->get($source));
    }

    /**
     * @param array<array-key, array|scalar|null> $parameters
     */
    private function populateMissingAttributes(Request $request, $parameters): void
    {
        $request->attributes->set('_api_query_parameters', $parameters);
        $request->attributes->set('_api_filters', $parameters);
    }
}
