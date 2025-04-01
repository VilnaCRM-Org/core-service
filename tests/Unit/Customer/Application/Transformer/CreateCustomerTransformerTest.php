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
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Factory\UlidFactory;

final class CreateCustomerTransformerTest extends UnitTestCase
{
    private UlidFactory $symfonyUlidFactory;
    private UlidTransformer $ulidTransformerMock;
    private UlidFactory $ulidFactoryMock;
    private CustomerFactoryInterface $customerFactoryMock;
    private CreateCustomerTransformer $createCustomerTransformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->symfonyUlidFactory = new UlidFactory();
        $this->ulidTransformerMock = $this
            ->createMock(UlidTransformer::class);
        $this->ulidFactoryMock = $this
            ->createMock(UlidFactory::class);
        $this->customerFactoryMock = $this
            ->createMock(CustomerFactoryInterface::class);
        $this->createCustomerTransformer = new CreateCustomerTransformer(
            $this->customerFactoryMock,
            $this->ulidTransformerMock,
            $this->ulidFactoryMock
        );
    }

    public function testTransform(): void
    {
        $testData = $this->prepareTestData();
        $command = $this->createCommandFromTestData($testData);
        $this->setExpectationsFromTestData($testData);

        $result = $this->createCustomerTransformer->transform($command);
        $this->assertSame($testData['customer'], $result);
    }

    /**
     * @param array<string, string|bool|CustomerType|CustomerStatus|Customer> $testData
     */
    private function createCommandFromTestData(
        array $testData
    ): CreateCustomerCommand {
        return $this->createCommand(
            $testData['initials'],
            $testData['email'],
            $testData['phone'],
            $testData['leadSource'],
            $testData['customerType'],
            $testData['customerStatus'],
            $testData['confirmed']
        );
    }

    /**
     * @param array<string, string|bool|CustomerType|CustomerStatus|Customer> $testData
     */
    private function setExpectationsFromTestData(array $testData): void
    {
        $this->setExpectations(
            $testData['customer'],
            $testData['initials'],
            $testData['email'],
            $testData['phone'],
            $testData['leadSource'],
            $testData['customerType'],
            $testData['customerStatus'],
            $testData['confirmed']
        );
    }

    /**
     * @return array<string, string|bool|CustomerType|CustomerStatus|Customer>
     */
    private function prepareTestData(): array
    {
        return [
            'customerType' => $this->createMock(CustomerType::class),
            'customerStatus' => $this->createMock(CustomerStatus::class),
            'initials' => $this->faker->name(),
            'email' => $this->faker->email(),
            'phone' => $this->faker->phoneNumber(),
            'leadSource' => 'Website',
            'confirmed' => true,
            'customer' => $this->createMock(Customer::class),
        ];
    }

    private function createCommand(
        string $initials,
        string $email,
        string $phone,
        string $leadSource,
        CustomerType $type,
        CustomerStatus $status,
        bool $confirmed
    ): CreateCustomerCommand {
        return new CreateCustomerCommand(
            $initials,
            $email,
            $phone,
            $leadSource,
            $type,
            $status,
            $confirmed,
        );
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
        $this->setupUlidExpectations($ulidObject);
        $this->setupCustomerFactoryExpectations(
            $customer,
            $initials,
            $email,
            $phone,
            $leadSource,
            $type,
            $status,
            $confirmed,
            $ulidObject
        );
    }

    private function setupUlidExpectations(Ulid $ulidObject): void
    {
        $this->ulidFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->symfonyUlidFactory->create());

        $this->ulidTransformerMock->expects($this->once())
            ->method('transformFromSymfonyUlid')
            ->willReturn($ulidObject);
    }

    private function setupCustomerFactoryExpectations(
        CustomerInterface $customer,
        string $initials,
        string $email,
        string $phone,
        string $leadSource,
        CustomerType $type,
        CustomerStatus $status,
        bool $confirmed,
        Ulid $ulidObject
    ): void {
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
