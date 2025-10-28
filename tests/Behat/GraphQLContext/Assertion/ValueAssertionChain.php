<?php

declare(strict_types=1);

namespace App\Tests\Behat\GraphQLContext\Assertion;

final class ValueAssertionChain
{
    /** @var array<ValueAssertionInterface> */
    private array $assertions;

    public function __construct()
    {
        $this->assertions = [
            new BooleanValueAssertion(),
            new NullValueAssertion(),
            new StringValueAssertion(),
        ];
    }

    public function assert(string $path, string $expectedValue, mixed $actualValue): void
    {
        foreach ($this->assertions as $assertion) {
            if ($assertion->canAssert($expectedValue)) {
                $assertion->assert($path, $expectedValue, $actualValue);
                return;
            }
        }
    }
}
