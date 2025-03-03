<?php

declare(strict_types=1);

namespace App\Customer\Application\Transformer;

class CreateTypeTransformer
{
    public function __construct(
        private CustomerF $userFactory,
        private UuidTransformer $transformer,
        private UuidFactory $uuidFactory,
    ) {
    }
}
