<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventListener;

final readonly class ApiQueryParametersSanitizer
{
    public function __construct(
        private ApiQueryKeyValidator $keyValidator
    ) {
    }

    /**
     * @param array<array-key, array|scalar|null>|scalar|null $parameters
     *
     * @return array<array-key, array|scalar|null>
     */
    public function sanitize($parameters, bool $allowIntegerKeys = false)
    {
        if (! is_array($parameters)) {
            return [];
        }

        /** @var array<array-key, array|scalar|null> $sanitized */
        $sanitized = [];

        foreach ($parameters as $key => $value) {
            if (! $this->keyValidator->allows($key, $allowIntegerKeys)) {
                continue;
            }

            $sanitized[$key] = $this->sanitizeValue($value);
        }

        return $sanitized;
    }

    /**
     * @param array<array-key, array|scalar|null>|scalar|null $value
     *
     * @return array<array-key, array|scalar|null>|scalar|null
     */
    private function sanitizeValue($value)
    {
        return is_array($value) ? $this->sanitize($value, true) : $value;
    }
}
