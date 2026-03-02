<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Transformer;

use App\Core\Customer\Application\MutationInput\CreateStatusMutationInput;

final class CreateStatusMutationInputTransformer
{
    /**
     * @param array{value?: string|null} $args
     */
    public function transform(array $args): CreateStatusMutationInput
    {
        return new CreateStatusMutationInput(
            $args['value'] ?? null
        );
    }
}
