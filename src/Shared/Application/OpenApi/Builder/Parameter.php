<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Builder;

final readonly class Parameter
{
    public function __construct(
        public string $name,
        public string $type,
        public string|int|array|bool $example,
        public ?int $maxLength = null,
        public ?string $format = null,
        public bool $required = true
    ) {
    }

    public static function required(
        string $name,
        string $type,
        string|int|array|bool $example,
        ?int $maxLength = null,
        ?string $format = null
    ): self {
        return new self($name, $type, $example, $maxLength, $format, true);
    }

    public static function optional(
        string $name,
        string $type,
        string|int|array|bool $example,
        ?int $maxLength = null,
        ?string $format = null
    ): self {
        return new self($name, $type, $example, $maxLength, $format, false);
    }
}
