<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Transformer;

use App\Core\Customer\Application\MutationInput\CreateCustomerMutationInput;

final class CreateCustomerMutationInputTransformer
{
    /**
     * @param array<string, mixed> $args
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
