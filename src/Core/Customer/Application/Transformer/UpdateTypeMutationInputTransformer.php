<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Transformer;

use App\Core\Customer\Application\MutationInput\UpdateTypeMutationInput;

final class UpdateTypeMutationInputTransformer
{
    /**
     * @param array{value?: string|null} $args
     */
    public function transform(array $args): UpdateTypeMutationInput
    {
        return new UpdateTypeMutationInput(
            $args['value'] ?? null
        );
    }
}
