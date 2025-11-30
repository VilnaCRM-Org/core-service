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
            $args['initials'] ?? null,
            $args['email'] ?? null,
            $args['phone'] ?? null,
            $args['leadSource'] ?? null,
            $args['type'] ?? null,
            $args['status'] ?? null,
            $args['confirmed'] ?? null
        );
    }
}
