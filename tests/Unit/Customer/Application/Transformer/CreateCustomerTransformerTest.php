<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Transformer;

use App\Customer\Application\Command\CreateCustomerCommand;
use App\Customer\Application\Transformer\CreateCustomerTransformer;
use App\Customer\Domain\Entity\Customer;
use App\Customer\Domain\Entity\CustomerInterface;
use App\Customer\Domain\Entity\CustomerStatus;
use App\Customer\Domain\Entity\CustomerType;
use App\Customer\Domain\Factory\CustomerFactoryInterface;
use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Infrastructure\Factory\UlidFactory as UlidFactoryInterface;
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Factory\UlidFactory;

final class CreateCustomerTransformerTest extends UnitTestCase
{
    private CustomerFactoryInterface $customerFactory;
    private UlidTransformer $transformer;
    private UlidFactory $symfonyUlidFactory;
    private UlidTransformer $ulidTransformerMock;
    private UlidFactory $ulidFactoryMock;
    private CustomerFactoryInterface $customerFactoryMock;
    private CreateCustomerTransformer $createCustomerTransformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customerFactory = $this->createMock(CustomerFactoryInterface::class);
        $this->transformer = new UlidTransformer(new UlidFactoryInterface());
        $this->symfonyUlidFactory = new UlidFactory();
        $this->ulidTransformerMock = $this->createMock(UlidTransformer::class);
        $this->ulidFactoryMock = $this->createMock(UlidFactory::class);
        $this->customerFactoryMock = $this->createMock(CustomerFactoryInterface::class);
        $this->createCustomerTransformer = new CreateCustomerTransformer(
            $this->customerFactoryMock,
            $this->ulidTransformerMock,
            $this->ulidFactoryMock
        );
    }

    public function testTransform(): void
    {
        $customerType = $this->createMock(CustomerType::class);
        $customerStatus = $this->createMock(CustomerStatus::class);
        $initials = $this->faker->name();
        $email = $this->faker->email();
        $phone = $this->faker->phoneNumber();
        $leadSource = 'Website';
        $type = $customerType;
        $status = $customerStatus;
        $confirmed = true;

        $customer = $this->createMock(Customer::class);

        $command = new CreateCustomerCommand(
            $initials,
            $email,
            $phone,
            $leadSource,
            $type,
            $status,
            $confirmed,
        );

        $this->setExpectations($customer, $initials, $email, $phone, $leadSource, $type, $status, $confirmed);

        $result = $this->createCustomerTransformer->transform($command);

        $this->assertSame($customer, $result);
    }

    private function setExpectations(
        CustomerInterface $customer,
        string $initials,
        string $email,
        string $phone,
        string $leadSource,
        CustomerType $type,
        CustomerStatus $status,
        bool $confirmed
    ): void {
        $ulidObject = $this->createMock(Ulid::class);

        $this->ulidFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->symfonyUlidFactory->create());

        $this->ulidTransformerMock->expects($this->once())
            ->method('transformFromSymfonyUlid')
            ->willReturn($ulidObject);

        $this->customerFactoryMock->expects($this->once())
            ->method('create')
            ->with(
                $initials,
                $email,
                $phone,
                $leadSource,
                $type,
                $status,
                $confirmed,
                $ulidObject
            )
            ->willReturn($customer);
    }
}
