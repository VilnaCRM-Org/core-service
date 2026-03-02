<?php

declare(strict_types=1);

namespace App\Tests\Behat\GraphQLContext\Assertion;

interface ValueAssertionInterface
{
    public function canAssert(string $expectedValue): bool;

    public function assert(string $path, string $expectedValue, mixed $actualValue): void;
}
