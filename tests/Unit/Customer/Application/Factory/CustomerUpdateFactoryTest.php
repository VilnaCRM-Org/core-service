<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Factory;

use App\Core\Customer\Application\Factory\CustomerUpdateFactory;
use App\Core\Customer\Application\Transformer\CustomerRelationTransformerInterface;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\ValueObject\CustomerUpdate;
use App\Tests\Unit\UnitTestCase;

final class CustomerUpdateFactoryTest extends UnitTestCase
{
    public function testCreateWithAllFieldsProvided(): void
    {
        $testData = $this->setupAllFieldsTestData();
        $this->setupRelationResolverForAllFields(
            $testData['relationResolver'],
            $testData['customer'],
            $testData['input'],
            $testData['customerType'],
            $testData['customerStatus']
        );

        $result = $testData['factory']->create($testData['customer'], $testData['input']);

        $this->assertAllFieldsResult($result, $testData);
    }

    public function testCreateWithMissingFieldsUsesExistingCustomerData(): void
    {
        $testData = $this->setupMissingFieldsTestData();
        $this->setupRelationResolverMocks(
            $testData['relationResolver'],
            $testData['customer'],
            $testData['type'],
            $testData['status']
        );

        $result = $testData['factory']->create($testData['customer'], []);

        $this->assertMissingFieldsResult($result, $testData);
    }

    public function testCreateWithPartialFieldsProvided(): void
    {
        $testData = $this->setupPartialFieldsTestData();
        $this->setupRelationResolverMocks(
            $testData['relationResolver'],
            $testData['customer'],
            $testData['type'],
            $testData['status']
        );

        $result = $testData['factory']->create($testData['customer'], $testData['input']);

        $this->assertPartialFieldsResult($result, $testData);
    }

    public function testCreateWithEmptyStringsUsesExistingCustomerData(): void
    {
        $testData = $this->setupEmptyStringsTestData();
        $this->setupRelationResolverMocks(
            $testData['relationResolver'],
            $testData['customer'],
            $testData['type'],
            $testData['status']
        );

        $result = $testData['factory']->create($testData['customer'], $testData['input']);

        $this->assertEmptyStringsResult($result, $testData);
    }

    public function testCreateWithWhitespaceOnlyStringsUsesExistingCustomerData(): void
    {
        $testData = $this->setupWhitespaceStringsTestData();
        $this->setupRelationResolverMocks(
            $testData['relationResolver'],
            $testData['customer'],
            $testData['type'],
            $testData['status']
        );

        $result = $testData['factory']->create($testData['customer'], $testData['input']);

        $this->assertWhitespaceStringsResult($result, $testData);
    }

    /** @return array<string, CustomerUpdateFactory|CustomerRelationTransformerInterface|Customer|CustomerType|CustomerStatus|array<string, string|bool>> */
    private function setupAllFieldsTestData(): array
    {
        $relationResolver = $this->createMock(CustomerRelationTransformerInterface::class);
        $customer = $this->createMock(Customer::class);
        $customerType = $this->createMock(CustomerType::class);
        $customerStatus = $this->createMock(CustomerStatus::class);

        return [
            'factory' => new CustomerUpdateFactory($relationResolver),
            'relationResolver' => $relationResolver,
            'customer' => $customer,
            'customerType' => $customerType,
            'customerStatus' => $customerStatus,
            'input' => [
                'initials' => 'AB',
                'email' => $this->faker->email(),
                'phone' => $this->faker->phoneNumber(),
                'leadSource' => 'website',
                'type' => '/api/customer_types/' . $this->faker->uuid(),
                'status' => '/api/customer_statuses/' . $this->faker->uuid(),
                'confirmed' => true,
            ],
        ];
    }

    /**
     * @param array<string, CustomerUpdateFactory|CustomerRelationTransformerInterface|Customer|CustomerType|CustomerStatus|array<string, string|bool>> $testData
     * @param array<string, string|bool> $input
     */
    private function setupRelationResolverForAllFields(
        CustomerRelationTransformerInterface $resolver,
        Customer $customer,
        array $input,
        CustomerType $customerType,
        CustomerStatus $customerStatus
    ): void {
        $resolver->expects(self::once())
            ->method('resolveType')
            ->with($input['type'], $customer)
            ->willReturn($customerType);
        $resolver->expects(self::once())
            ->method('resolveStatus')
            ->with($input['status'], $customer)
            ->willReturn($customerStatus);
    }

    /** @param array<string, CustomerUpdateFactory|CustomerRelationTransformerInterface|Customer|CustomerType|CustomerStatus|array<string, string|bool>> $testData */
    private function assertAllFieldsResult(CustomerUpdate $result, array $testData): void
    {
        self::assertInstanceOf(CustomerUpdate::class, $result);
        self::assertSame($testData['input']['initials'], $result->newInitials);
        self::assertSame($testData['input']['email'], $result->newEmail);
        self::assertSame($testData['input']['phone'], $result->newPhone);
        self::assertSame($testData['input']['leadSource'], $result->newLeadSource);
        self::assertSame($testData['customerType'], $result->newType);
        self::assertSame($testData['customerStatus'], $result->newStatus);
        self::assertTrue($result->newConfirmed);
    }

    /** @return array<string, CustomerUpdateFactory|CustomerRelationTransformerInterface|Customer|CustomerType|CustomerStatus|array<string, string|bool>> */
    private function setupMissingFieldsTestData(): array
    {
        $relationResolver = $this->createMock(CustomerRelationTransformerInterface::class);
        $customer = $this->createMock(Customer::class);
        $existingData = $this->createExistingData();

        $this->setupCustomerMockForExistingData($customer, $existingData);

        return [
            'factory' => new CustomerUpdateFactory($relationResolver),
            'relationResolver' => $relationResolver,
            'customer' => $customer,
            'type' => $this->createMock(CustomerType::class),
            'status' => $this->createMock(CustomerStatus::class),
            'existingData' => $existingData,
        ];
    }

    /** @return array<string, string|bool> */
    private function createExistingData(): array
    {
        return [
            'initials' => 'CD',
            'email' => $this->faker->email(),
            'phone' => $this->faker->phoneNumber(),
            'leadSource' => 'referral',
            'confirmed' => false,
        ];
    }

    /** @param array<string, string|bool> $existingData */
    private function setupCustomerMockForExistingData(Customer $customer, array $existingData): void
    {
        $customer->method('getInitials')->willReturn($existingData['initials']);
        $customer->method('getEmail')->willReturn($existingData['email']);
        $customer->method('getPhone')->willReturn($existingData['phone']);
        $customer->method('getLeadSource')->willReturn($existingData['leadSource']);
        $customer->method('isConfirmed')->willReturn($existingData['confirmed']);
    }

    /** @param array<string, CustomerUpdateFactory|CustomerRelationTransformerInterface|Customer|CustomerType|CustomerStatus|array<string, string|bool>> $testData */
    private function assertMissingFieldsResult(CustomerUpdate $result, array $testData): void
    {
        self::assertInstanceOf(CustomerUpdate::class, $result);
        self::assertSame($testData['existingData']['initials'], $result->newInitials);
        self::assertSame($testData['existingData']['email'], $result->newEmail);
        self::assertSame($testData['existingData']['phone'], $result->newPhone);
        self::assertSame($testData['existingData']['leadSource'], $result->newLeadSource);
        self::assertSame($testData['type'], $result->newType);
        self::assertSame($testData['status'], $result->newStatus);
        self::assertFalse($result->newConfirmed);
    }

    /** @return array<string, CustomerUpdateFactory|CustomerRelationTransformerInterface|Customer|CustomerType|CustomerStatus|array<string, string|bool>> */
    private function setupPartialFieldsTestData(): array
    {
        $relationResolver = $this->createMock(CustomerRelationTransformerInterface::class);
        $customer = $this->createMock(Customer::class);
        $existingData = ['initials' => 'EF', 'leadSource' => 'partner', 'confirmed' => true];

        $customer->method('getInitials')->willReturn($existingData['initials']);
        $customer->method('getLeadSource')->willReturn($existingData['leadSource']);
        $customer->method('isConfirmed')->willReturn($existingData['confirmed']);

        return [
            'factory' => new CustomerUpdateFactory($relationResolver),
            'relationResolver' => $relationResolver,
            'customer' => $customer,
            'type' => $this->createMock(CustomerType::class),
            'status' => $this->createMock(CustomerStatus::class),
            'input' => ['email' => $this->faker->email(), 'phone' => $this->faker->phoneNumber()],
            'existingData' => $existingData,
        ];
    }

    private function setupRelationResolverMocks(
        CustomerRelationTransformerInterface $resolver,
        Customer $customer,
        CustomerType $type,
        CustomerStatus $status
    ): void {
        $resolver->expects(self::once())
            ->method('resolveType')
            ->with(null, $customer)
            ->willReturn($type);
        $resolver->expects(self::once())
            ->method('resolveStatus')
            ->with(null, $customer)
            ->willReturn($status);
    }

    /** @param array<string, CustomerUpdateFactory|CustomerRelationTransformerInterface|Customer|CustomerType|CustomerStatus|array<string, string|bool>> $testData */
    private function assertPartialFieldsResult(CustomerUpdate $result, array $testData): void
    {
        self::assertInstanceOf(CustomerUpdate::class, $result);
        self::assertSame($testData['existingData']['initials'], $result->newInitials);
        self::assertSame($testData['input']['email'], $result->newEmail);
        self::assertSame($testData['input']['phone'], $result->newPhone);
        self::assertSame($testData['existingData']['leadSource'], $result->newLeadSource);
        self::assertSame($testData['type'], $result->newType);
        self::assertSame($testData['status'], $result->newStatus);
        self::assertTrue($result->newConfirmed);
    }

    /** @return array<string, CustomerUpdateFactory|CustomerRelationTransformerInterface|Customer|CustomerType|CustomerStatus|array<string, string|bool>> */
    private function setupEmptyStringsTestData(): array
    {
        $relationResolver = $this->createMock(CustomerRelationTransformerInterface::class);
        $customer = $this->createMock(Customer::class);
        $existingData = [
            'initials' => 'GH',
            'email' => $this->faker->email(),
            'phone' => $this->faker->phoneNumber(),
            'leadSource' => 'direct',
            'confirmed' => true,
        ];

        $this->setupCustomerMockForExistingData($customer, $existingData);

        return [
            'factory' => new CustomerUpdateFactory($relationResolver),
            'relationResolver' => $relationResolver,
            'customer' => $customer,
            'type' => $this->createMock(CustomerType::class),
            'status' => $this->createMock(CustomerStatus::class),
            'input' => [
                'initials' => '',
                'email' => '',
                'phone' => '',
                'leadSource' => '',
            ],
            'existingData' => $existingData,
        ];
    }

    /** @param array<string, CustomerUpdateFactory|CustomerRelationTransformerInterface|Customer|CustomerType|CustomerStatus|array<string, string|bool>> $testData */
    private function assertEmptyStringsResult(CustomerUpdate $result, array $testData): void
    {
        self::assertInstanceOf(CustomerUpdate::class, $result);
        self::assertSame($testData['existingData']['initials'], $result->newInitials);
        self::assertSame($testData['existingData']['email'], $result->newEmail);
        self::assertSame($testData['existingData']['phone'], $result->newPhone);
        self::assertSame($testData['existingData']['leadSource'], $result->newLeadSource);
        self::assertSame($testData['type'], $result->newType);
        self::assertSame($testData['status'], $result->newStatus);
        self::assertTrue($result->newConfirmed);
    }

    /** @return array<string, CustomerUpdateFactory|CustomerRelationTransformerInterface|Customer|CustomerType|CustomerStatus|array<string, string|bool>> */
    private function setupWhitespaceStringsTestData(): array
    {
        $relationResolver = $this->createMock(CustomerRelationTransformerInterface::class);
        $customer = $this->createMock(Customer::class);
        $existingData = [
            'initials' => 'IJ',
            'email' => $this->faker->email(),
            'phone' => $this->faker->phoneNumber(),
            'leadSource' => 'campaign',
            'confirmed' => false,
        ];

        $this->setupCustomerMockForExistingData($customer, $existingData);

        return [
            'factory' => new CustomerUpdateFactory($relationResolver),
            'relationResolver' => $relationResolver,
            'customer' => $customer,
            'type' => $this->createMock(CustomerType::class),
            'status' => $this->createMock(CustomerStatus::class),
            'input' => [
                'initials' => '   ',
                'email' => "\t\n",
                'phone' => '  ',
                'leadSource' => "\n\t ",
            ],
            'existingData' => $existingData,
        ];
    }

    /** @param array<string, CustomerUpdateFactory|CustomerRelationTransformerInterface|Customer|CustomerType|CustomerStatus|array<string, string|bool>> $testData */
    private function assertWhitespaceStringsResult(CustomerUpdate $result, array $testData): void
    {
        self::assertInstanceOf(CustomerUpdate::class, $result);
        self::assertSame($testData['existingData']['initials'], $result->newInitials);
        self::assertSame($testData['existingData']['email'], $result->newEmail);
        self::assertSame($testData['existingData']['phone'], $result->newPhone);
        self::assertSame($testData['existingData']['leadSource'], $result->newLeadSource);
        self::assertSame($testData['type'], $result->newType);
        self::assertSame($testData['status'], $result->newStatus);
        self::assertFalse($result->newConfirmed);
    }
}
