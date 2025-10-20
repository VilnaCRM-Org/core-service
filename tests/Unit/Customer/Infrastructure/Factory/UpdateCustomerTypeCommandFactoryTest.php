<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Infrastructure\Factory;

use App\Core\Customer\Application\Command\UpdateCustomerTypeCommand;
use App\Core\Customer\Application\Factory\UpdateTypeCommandFactoryInterface;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\ValueObject\CustomerTypeUpdate;
use App\Core\Customer\Infrastructure\Factory\UpdateTypeCommandFactory;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class UpdateCustomerTypeCommandFactoryTest extends UnitTestCase
{
    private UpdateTypeCommandFactoryInterface $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new UpdateTypeCommandFactory();
    }

    public function testCreateAssignsCorrectPropertiesToCommand(): void
    {
        $value = $this->faker->word();
        $customerType = $this->createMock(CustomerType::class);
        $update = new CustomerTypeUpdate($value);

        $command = $this->factory->create($customerType, $update);

        $this->assertCommand($command, $customerType, $update);
    }

    private function assertCommand(
        UpdateCustomerTypeCommand $command,
        CustomerType|MockObject $customerType,
        CustomerTypeUpdate $update
    ): void {
        $this->assertSame($customerType, $command->customerType);
        $this->assertSame($update, $command->update);
    }
}
