<?php

declare(strict_types=1);

namespace App\Tests\Memory\Support;

use App\Tests\Support\Memory\TrackedRequestHolder;
use LogicException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class TrackedRequestHolderTest extends TestCase
{
    public function testRequireTrackedRequestThrowsWhenHolderIsEmpty(): void
    {
        $holder = new TrackedRequestHolder();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Expected a tracked main request, but none was recorded.');

        $holder->requireTrackedRequest();
    }

    public function testTrackAndClearManageTheTrackedRequest(): void
    {
        $holder = new TrackedRequestHolder();
        $request = Request::create('/memory');

        $holder->track($request);
        self::assertSame($request, $holder->requireTrackedRequest());

        $holder->clear();

        $this->expectException(LogicException::class);
        $holder->requireTrackedRequest();
    }
}
