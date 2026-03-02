<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Stub;

use ApiPlatform\OpenApi\Model\Parameter;
use App\Shared\Application\OpenApi\Cleaner\PathParameterCleaner;
use App\Shared\Application\OpenApi\Cleaner\PathParameterCleanerInterface;

final class PathParameterCleanerSpy implements PathParameterCleanerInterface
{
    private int $callCount = 0;
    private PathParameterCleaner $decorated;

    public function __construct()
    {
        $this->decorated = new PathParameterCleaner();
    }

    public function clean(mixed $parameter): mixed
    {
        if ($parameter instanceof Parameter) {
            $this->callCount++;
        }

        return $this->decorated->clean($parameter);
    }

    public function getCallCount(): int
    {
        return $this->callCount;
    }
}
