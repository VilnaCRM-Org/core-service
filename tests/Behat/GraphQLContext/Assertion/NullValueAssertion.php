<?php

declare(strict_types=1);

namespace App\Tests\Behat\GraphQLContext\Assertion;

use Webmozart\Assert\Assert;

final class NullValueAssertion implements ValueAssertionInterface
{
    public function canAssert(string $expectedValue): bool
    {
        return strtolower($expectedValue) === 'null';
    }

    public function assert(string $path, string $expectedValue, mixed $actualValue): void
    {
        Assert::null(
            $actualValue,
            sprintf('Expected %s to be null, got %s', $path, var_export($actualValue, true))
        );
    }
}
