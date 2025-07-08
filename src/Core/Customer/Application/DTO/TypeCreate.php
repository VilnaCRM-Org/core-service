<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class TypeCreate
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $value
    ) {
    }
}
