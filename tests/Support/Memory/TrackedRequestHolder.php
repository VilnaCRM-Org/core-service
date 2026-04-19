<?php

declare(strict_types=1);

namespace App\Tests\Support\Memory;

use LogicException;
use Symfony\Component\HttpFoundation\Request;

final class TrackedRequestHolder
{
    /**
     * @var list<Request>
     */
    private array $trackedRequests = [];

    public function track(Request $request): void
    {
        $this->trackedRequests[] = $request;
    }

    public function requireTrackedRequest(): Request
    {
        $trackedRequests = $this->requireTrackedRequests();

        return $trackedRequests[array_key_last($trackedRequests)];
    }

    /**
     * @return list<Request>
     */
    public function requireTrackedRequests(): array
    {
        if ($this->trackedRequests === []) {
            throw new LogicException('Expected a tracked main request, but none was recorded.');
        }

        return $this->trackedRequests;
    }

    public function clear(): void
    {
        $this->trackedRequests = [];
    }
}
