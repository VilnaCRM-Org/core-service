<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Builder;

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
        return $this->requirement->toBool();
    }

    public static function required(
        string $name,
        string $type,
        string|int|array|bool|null $example,
        ?int $maxLength = null,
        ?string $format = null,
        ?array $items = null
    ): self {
        return new self($name, $type, $example, $maxLength, $format, Requirement::REQUIRED, $items);
    }

    public static function optional(
        string $name,
        string $type,
        string|int|array|bool|null $example,
        ?int $maxLength = null,
        ?string $format = null,
        ?array $items = null
    ): self {
        return new self($name, $type, $example, $maxLength, $format, Requirement::OPTIONAL, $items);
    }
}
