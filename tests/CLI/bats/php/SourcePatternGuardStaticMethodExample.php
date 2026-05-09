<?php

declare(strict_types=1);

namespace App\Shared\Application;

final class SourcePatternGuardStaticMethodExample
{
    public static function create(): string
    {
        return 'bad';
    }
}
