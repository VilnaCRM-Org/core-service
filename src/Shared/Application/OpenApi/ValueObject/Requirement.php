<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\ValueObject;

enum Requirement
{
    case REQUIRED;
    case OPTIONAL;
}
