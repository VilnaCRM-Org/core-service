<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Transformer;

use App\Core\Customer\Application\MutationInput\CreateTypeMutationInput;

final class CreateTypeMutationInputTransformer
{
    /**
     * @param array{value?: string|null} $args
     */
    public function transform(array $args): CreateTypeMutationInput
    {
        return new CreateTypeMutationInput(
            $args['value'] ?? null
        );
    }
}
