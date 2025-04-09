<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Infrastructure\Factory;

use App\Customer\Application\Command\UpdateCustomerTypeCommand;
use App\Customer\Application\Factory\UpdateCustomerTypeCommandFactoryInterface;
use App\Customer\Domain\Entity\CustomerType;
use App\Customer\Domain\ValueObject\CustomerTypeUpdate;
use App\Customer\Infrastructure\Factory\UpdateCustomerTypeCommandFactory;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class UpdateCustomerTypeCommandFactoryTest extends UnitTestCase
{
    private UpdateCustomerTypeCommandFactoryInterface $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new UpdateCustomerTypeCommandFactory();
    }

    public function testCreate(): void
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
