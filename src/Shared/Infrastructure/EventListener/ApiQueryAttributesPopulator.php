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

        if ($request->attributes->has('_api_query_parameters')) {
            $request->attributes->set(
                '_api_filters',
                $request->attributes->get('_api_query_parameters')
            );
            return;
        }

        if ($request->attributes->has('_api_filters')) {
            $request->attributes->set(
                '_api_query_parameters',
                $request->attributes->get('_api_filters')
            );
            return;
        }

        $request->attributes->set('_api_query_parameters', $parameters);
        $request->attributes->set('_api_filters', $parameters);
    }
}
