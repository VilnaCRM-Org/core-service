<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\ValueObject;

final readonly class Parameter
{
    public function __construct(
        public string $name,
        public string $type,
        public string|int|array|bool|null $example,
        public ?int $maxLength = null,
        public ?string $format = null,
        public Requirement $requirement = Requirement::REQUIRED,
        public ?array $items = null
    ) {
    }

    public function isRequired(): bool
    {
        return $this->requirement === Requirement::REQUIRED;
    }
}
