<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Command;

use App\Customer\Application\Command\CreateCustomerCommandResponse;
use App\Customer\Domain\Entity\Customer;
use App\Tests\Unit\UnitTestCase;

final class CreateCustomerCommandResponseTest extends UnitTestCase
{
    public function testResponseStoresCustomer(): void
    {
        $dummyCustomer = $this->createStub(Customer::class);
        $response = new CreateCustomerCommandResponse($dummyCustomer);

        $this->assertSame($dummyCustomer, $response->customer);
    }
}
