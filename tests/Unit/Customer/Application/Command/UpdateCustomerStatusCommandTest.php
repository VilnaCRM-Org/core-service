<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Command;

use App\Customer\Application\Command\UpdateCustomerStatusCommand;
use App\Customer\Domain\Entity\CustomerStatus;
use App\Customer\Domain\ValueObject\CustomerStatusUpdate;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class UpdateCustomerStatusCommandTest extends UnitTestCase
{
    public function testConstruct(): void
    {
        $value = $this->faker->word();
        $customerStatus = $this->createMock(CustomerStatus::class);
        $update = new CustomerStatusUpdate($value);

        $command = new UpdateCustomerStatusCommand($customerStatus, $update);

        $this->assertCommand($command, $customerStatus, $update);
    }

    private function assertCommand(
        UpdateCustomerStatusCommand $command,
        CustomerStatus|MockObject $customerStatus,
        CustomerStatusUpdate $update
    ): void {
        $this->assertSame($customerStatus, $command->customerStatus);
        $this->assertSame($update, $command->update);
    }
}
