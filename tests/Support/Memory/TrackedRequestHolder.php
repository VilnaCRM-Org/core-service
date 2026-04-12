<?php

declare(strict_types=1);

namespace App\Tests\Support\Memory;

use LogicException;
use Symfony\Component\HttpFoundation\Request;

final class TrackedRequestHolder
{
    private ?Request $trackedRequest = null;

    public function track(Request $request): void
    {
        $this->trackedRequest = $request;
    }

    public function requireTrackedRequest(): Request
    {
        if ($this->trackedRequest === null) {
            throw new LogicException('Expected a tracked main request, but none was recorded.');
        }

        return $this->trackedRequest;
    }

    public function clear(): void
    {
        $this->trackedRequest = null;
    }
}
