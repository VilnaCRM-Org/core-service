<?php

declare(strict_types=1);

namespace App\Tests\Behat\GraphQLContext\Assertion;

use Webmozart\Assert\Assert;

final class BooleanValueAssertion implements ValueAssertionInterface
{
    #[\Override]
    public function canAssert(string $expectedValue): bool
    {
        return in_array(strtolower($expectedValue), ['true', 'false'], true);
    }

    #[\Override]
    public function assert(string $path, string $expectedValue, mixed $actualValue): void
    {
        $expected = filter_var($expectedValue, FILTER_VALIDATE_BOOLEAN);

        Assert::same(
            $actualValue,
            $expected,
            sprintf(
                'Expected %s to be %s, got %s',
                $path,
                var_export($expected, true),
                var_export($actualValue, true)
            )
        );
    }
}
