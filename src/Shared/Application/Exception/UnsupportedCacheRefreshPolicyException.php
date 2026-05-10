<?php

declare(strict_types=1);

namespace App\Shared\Application\Exception;

final class UnsupportedCacheRefreshPolicyException extends \RuntimeException
{
    public function __construct(string $context)
    {
        parent::__construct(sprintf('Unsupported cache context "%s".', $context));
    }
}
