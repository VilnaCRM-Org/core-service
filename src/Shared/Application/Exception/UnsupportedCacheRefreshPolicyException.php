<?php

declare(strict_types=1);

namespace App\Shared\Application\Exception;

final class UnsupportedCacheRefreshPolicyException extends \RuntimeException
{
    public static function forContext(string $context): self
    {
        return new self(sprintf('Unsupported cache context "%s".', $context));
    }
}
