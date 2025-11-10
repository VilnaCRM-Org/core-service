<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Transformer;

use App\Core\Customer\Application\MutationInput\CreateCustomerMutationInput;

final class CreateCustomerMutationInputTransformer
{
    /**
     * @param array{
     *     initials?: string|null,
     *     email?: string|null,
     *     phone?: string|null,
     *     leadSource?: string|null,
     *     type?: string|null,
     *     status?: string|null,
     *     confirmed?: bool|null
     * } $args
     */
    public function transform(array $args): CreateCustomerMutationInput
    {
        return new CreateCustomerMutationInput(
            $this->extractString($args, 'initials'),
            $this->extractString($args, 'email'),
            $this->extractString($args, 'phone'),
            $this->extractString($args, 'leadSource'),
            $this->extractString($args, 'type'),
            $this->extractString($args, 'status'),
            $this->extractBool($args, 'confirmed')
        );
    }

    /**
     * @param array<string, string|bool|null> $args
     */
    private function extractString(array $args, string $key): ?string
    {
        return $args[$key] ?? null;
    }

    /**
     * @param array<string, string|bool|null> $args
     */
    private function extractBool(array $args, string $key): ?bool
    {
        return $args[$key] ?? null;
    }
}
