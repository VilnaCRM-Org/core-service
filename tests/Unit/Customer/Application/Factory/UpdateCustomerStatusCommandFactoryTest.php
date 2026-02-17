<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Factory;

use App\Core\Customer\Application\Command\UpdateCustomerStatusCommand;
use App\Core\Customer\Application\Factory\UpdateStatusCommandFactory;
use App\Core\Customer\Application\Factory\UpdateStatusCommandFactoryInterface;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\ValueObject\CustomerStatusUpdate;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class UpdateCustomerStatusCommandFactoryTest extends UnitTestCase
{
    private UpdateStatusCommandFactoryInterface $factory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new UpdateStatusCommandFactory();
    }

    public function testCreate(): void
    {
        $value = $this->faker->word();
        $customerStatus = $this->createMock(CustomerStatus::class);
        $update = new CustomerStatusUpdate($value);

        $command = $this->factory->create($customerStatus, $update);

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
