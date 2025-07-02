<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Command;

use App\Core\Customer\Application\Command\CreateCustomerCommand;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Factory\CustomerFactory;
use App\Core\Customer\Domain\Factory\CustomerFactoryInterface;
use App\Shared\Infrastructure\Factory\UlidFactory;
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class CreateCustomerCommandTest extends UnitTestCase
{
    private CustomerFactoryInterface $customerFactory;
    private UlidTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customerFactory = new CustomerFactory();
        $this->transformer = new UlidTransformer(new UlidFactory());
    }

    public function testConstructorAcceptsCustomer(): void
    {
        $customer = $this->createCustomer($this->getCommandParams());

        $command = new CreateCustomerCommand($customer);

        $this->assertInstanceOf(CreateCustomerCommand::class, $command);
        $this->assertSame($customer, $command->customer);
    }

    /**
     * @return (MockObject&CustomerStatus|MockObject&CustomerType|bool|string)[]
     *
     * @psalm-return array{initials: string, email: string, phone: string, leadSource: string, type: MockObject&CustomerType, status: MockObject&CustomerStatus, confirmed: bool}
     */
    private function getCommandParams(): array
    {
        return [
            'initials' => $this->faker->name(),
            'email' => $this->faker->email(),
            'phone' => $this->faker->phoneNumber(),
            'leadSource' => $this->faker->word(),
            'type' => $this->createMock(CustomerType::class),
            'status' => $this->createMock(CustomerStatus::class),
            'confirmed' => $this->faker->boolean(),
        ];
    }

    /**
     * @param array<string, string|CustomerType|CustomerStatus> $params
     */
    private function createCustomer(array $params): Customer
    {
        $ulid = $this->faker->ulid();

        return $this->customerFactory->create(
            $params['initials'],
            $params['email'],
            $params['phone'],
            $params['leadSource'],
            $params['type'],
            $params['status'],
            $params['confirmed'],
            $this->transformer->transformFromSymfonyUlid($ulid),
        );
    }
}
