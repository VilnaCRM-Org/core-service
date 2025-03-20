<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Command;

use App\Customer\Application\Command\CreateTypeCommandResponse;
use App\Customer\Domain\Entity\CustomerType;
use App\Tests\Unit\UnitTestCase;

final class CreateTypeCommandResponseTest extends UnitTestCase
{
    public function testResponseStoresCustomerType(): void
    {
        $dummyCustomerType = $this->createStub(CustomerType::class);
        $response = new CreateTypeCommandResponse($dummyCustomerType);

        $this->assertSame($dummyCustomerType, $response->customerType);
    }
}
