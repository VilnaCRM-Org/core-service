<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Infrastructure\Factory;

use App\Customer\Application\Command\UpdateCustomerStatusCommand;
use App\Customer\Application\Factory\UpdateStatusCommandFactoryInterface;
use App\Customer\Domain\Entity\CustomerStatus;
use App\Customer\Domain\ValueObject\CustomerStatusUpdate;
use App\Customer\Infrastructure\Factory\UpdateStatusCommandFactory;
use App\Tests\Unit\UnitTestCase;
use Faker\Factory;
use PHPUnit\Framework\MockObject\MockObject;

final class UpdateCustomerStatusCommandFactoryTest extends UnitTestCase
{
    private UpdateStatusCommandFactoryInterface $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new UpdateStatusCommandFactory();
    }

    public function testCreate(): void
    {
        $faker = Factory::create();
        $value = $faker->word();
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
