<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability;

use Symfony\Component\HttpFoundation\Request;

final class ApiEndpointMetricDimensionsResolver
{
    /**
     * @return array{Endpoint: string, Operation: string}
     */
    public function dimensions(Request $request): array
    {
        return [
            'Endpoint' => $this->endpoint($request),
            'Operation' => $this->operation($request),
        ];
    }

    private function endpoint(Request $request): string
    {
        $path = $request->getPathInfo();
        $resourceClass = $request->attributes->getString('_api_resource_class', '');
        if ($resourceClass === '') {
            return $path;
        }

        $parts = explode('\\', $resourceClass);

        return $parts[count($parts) - 1];
    }

    private function operation(Request $request): string
    {
        $operationName = $request->attributes->getString('_api_operation_name', '');
        if ($operationName !== '') {
            return $operationName;
        }

        return strtolower($request->getMethod());
    }
}
