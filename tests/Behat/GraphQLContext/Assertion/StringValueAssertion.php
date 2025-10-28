<?php

declare(strict_types=1);

namespace App\Tests\Behat\GraphQLContext\Assertion;

use Webmozart\Assert\Assert;

final class StringValueAssertion implements ValueAssertionInterface
{
    public function canAssert(string $expectedValue): bool
    {
        return true;
    }

    public function assert(string $path, string $expectedValue, mixed $actualValue): void
    {
        Assert::eq(
            (string) $actualValue,
            $expectedValue,
            sprintf('Expected %s to be "%s", got "%s"', $path, $expectedValue, $actualValue)
        );
    }
}
