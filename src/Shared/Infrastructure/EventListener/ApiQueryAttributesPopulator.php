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

        $this->setAttributeIfMissing($request, '_api_query_parameters', $parameters);
        $this->setAttributeIfMissing($request, '_api_filters', $parameters);
    }

    /**
     * @param array<array-key, array|scalar|null> $parameters
     */
    private function setAttributeIfMissing(
        Request $request,
        string $attribute,
        $parameters
    ): void {
        if (! $request->attributes->has($attribute)) {
            $request->attributes->set($attribute, $parameters);
        }
    }
}
