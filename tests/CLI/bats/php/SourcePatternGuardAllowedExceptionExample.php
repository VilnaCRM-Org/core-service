<?php

declare(strict_types=1);

namespace App\Shared\Application;

/** @psalm-suppress UnusedClass */
final class SourcePatternGuardAllowedExceptionExample
{
    public function create(): \RuntimeException
    {
        return new \RuntimeException('allowed');
    }
}
