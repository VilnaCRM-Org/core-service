<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\DTO;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\OpenApi\Model\Parameter;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CustomerCreate
{
    public function __construct(
        public ?string $initials = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $leadSource = null,
        #[ApiProperty(schema: ['type' => 'string', 'format' => 'iri-reference'])]
        public ?string $type = null,
        #[ApiProperty(schema: ['type' => 'string', 'format' => 'iri-reference'])]
        public ?string $status = null,
        public ?bool $confirmed = null,
    ) {
    }
}
