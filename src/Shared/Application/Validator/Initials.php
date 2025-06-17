<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
final class Initials extends Constraint
{
    private bool $optional = false;

    public function __construct(
        ?array $groups = null,
        mixed $payload = null,
        ?bool $optional = null,
    ) {
        $this->optional = $optional ?? $this->optional;
        parent::__construct([], $groups, $payload);
    }
/** @psalm-suppress PossiblyUnusedMethod */
    public function isOptional(): bool
    {
        return $this->optional;
    }
}
