<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Transformer;

use App\Core\Customer\Application\MutationInput\UpdateCustomerMutationInput;

use function array_key_exists;

final class UpdateCustomerMutationInputTransformer
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
    public function transform(array $args): UpdateCustomerMutationInput
    {
        return new UpdateCustomerMutationInput(
            $this->valueFor($args, 'initials'),
            $this->valueFor($args, 'email'),
            $this->valueFor($args, 'phone'),
            $this->valueFor($args, 'leadSource'),
            $this->valueFor($args, 'type'),
            $this->valueFor($args, 'status'),
            $this->valueFor($args, 'confirmed')
        );
    }

    /**
     * @param array<string, string|bool|null> $args
     */
    private function valueFor(array $args, string $key): string|bool|null
    {
        return array_key_exists($key, $args) ? $args[$key] : null;
    }
}
