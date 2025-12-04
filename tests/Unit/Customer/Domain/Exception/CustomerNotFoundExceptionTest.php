<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Domain\Exception;

use App\Core\Customer\Domain\Exception\CustomerNotFoundException;
use App\Tests\Unit\UnitTestCase;

final class CustomerNotFoundExceptionTest extends UnitTestCase
{
    public function testWithId(): void
    {
        $id = (string) $this->faker->ulid();
        $exception = CustomerNotFoundException::withId($id);

        $this->assertInstanceOf(CustomerNotFoundException::class, $exception);
        $this->assertSame(
            sprintf('Customer with id "%s" not found', $id),
            $exception->getMessage()
        );
    }
}
