<?php

declare(strict_types=1);

namespace App\Tests\Support\Memory;

use ApiPlatform\Symfony\Bundle\Test\Client;
use PHPUnit\Framework\TestCase;

final readonly class SameKernelRequestMemoryProbe
{
    public function __construct(
        private TrackedRequestHolder $trackedRequestHolder,
    ) {
    }

    public static function fromClient(Client $client): self
    {
        $trackedRequestHolder = $client->getContainer()->get(TrackedRequestHolder::class);
        TestCase::assertInstanceOf(TrackedRequestHolder::class, $trackedRequestHolder);

        return new self($trackedRequestHolder);
    }

    public function assertRequestIsReleasedBetweenSameKernelRequests(
        TestCase $testCase,
        Client $client,
        string $label,
        callable $exercise,
    ): void {
        $watcher = new ObjectDeallocationWatcher();

        $this->trackedRequestHolder->clear();
        $exercise($client);

        $trackedRequest = $this->trackedRequestHolder->requireTrackedRequest();
        $watcher->expect($trackedRequest, "{$label} request");
        $this->trackedRequestHolder->clear();

        $exercise($client);
        $this->trackedRequestHolder->clear();

        unset($trackedRequest);

        $watcher->assertAllReleased($testCase);
    }
}
