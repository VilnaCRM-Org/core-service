<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

final class HydraAtTypeExampleUpdater
{
    /**
     * @return array<string, mixed>|null
     */
    public function update(array $example): ?array
    {
        if ($example === [] || ! array_key_exists('type', $example)) {
            return null;
        }

        if (array_key_exists('@type', $example)) {
            return null;
        }

        $example['@type'] = $example['type'];
        unset($example['type']);

        return $example;
    }
}
