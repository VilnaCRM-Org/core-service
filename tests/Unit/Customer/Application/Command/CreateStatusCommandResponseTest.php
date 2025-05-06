<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Command;

use App\Core\Customer\Application\Command\CreateStatusCommandResponse;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Tests\Unit\UnitTestCase;

final class CreateStatusCommandResponseTest extends UnitTestCase
{
    public function testResponseStoresCustomerStatus(): void
    {
        $dummyCustomerStatus = $this->createStub(CustomerStatus::class);
        $response = new CreateStatusCommandResponse($dummyCustomerStatus);

        $this->assertSame($dummyCustomerStatus, $response->customerStatus);
    }
}
