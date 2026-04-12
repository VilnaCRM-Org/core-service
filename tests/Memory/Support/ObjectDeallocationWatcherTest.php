<?php

declare(strict_types=1);

namespace App\Tests\Memory\Support;

use App\Tests\Support\Memory\ObjectDeallocationWatcher;
use PHPUnit\Framework\TestCase;

final class ObjectDeallocationWatcherTest extends TestCase
{
    public function testAssertAllReleasedDoesNothingWhenNoObjectsWereTracked(): void
    {
        $watcher = new ObjectDeallocationWatcher();

        $watcher->assertAllReleased($this);

        self::addToAssertionCount(1);
    }

    public function testAssertAllReleasedPassesWhenTrackedObjectsAreReleased(): void
    {
        $watcher = new ObjectDeallocationWatcher();
        $trackedObject = new \stdClass();

        $watcher->expect($trackedObject, 'released object');
        unset($trackedObject);

        $watcher->assertAllReleased($this);
    }
}
