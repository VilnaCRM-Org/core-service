<?php

declare(strict_types=1);

namespace App\Tests\Behat\GraphQLContext\Assertion;

final class ValueAssertionChain
{
    /**
     * @param array<ValueAssertionInterface> $assertions
     */
    public function __construct(
        private array $assertions = [],
    ) {
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
