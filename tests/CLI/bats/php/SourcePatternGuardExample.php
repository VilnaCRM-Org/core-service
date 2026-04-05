<?php

declare(strict_types=1);

namespace App\Shared\Application;

final class SourcePatternGuardExample
{
    public function createObject(): object
    {
        return new \stdClass();
    }
}
