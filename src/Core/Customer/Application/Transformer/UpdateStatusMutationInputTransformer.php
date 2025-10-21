<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Transformer;

use App\Core\Customer\Application\MutationInput\UpdateStatusMutationInput;

final class UpdateStatusMutationInputTransformer
{
    /**
     * @param array<string, mixed> $args
     */
    public function transform(array $args): UpdateStatusMutationInput
    {
        return new UpdateStatusMutationInput(
            $args['value'] ?? null
        );
    }
}
