<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Domain\ValueObject;

use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\ValueObject\CustomerUpdate;
use App\Shared\Infrastructure\Factory\UlidFactory as UlidFactoryInterface;
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use App\Shared\Infrastructure\Transformer\UlidTypeConverter;
use App\Shared\Infrastructure\Validator\UlidValidator;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Factory\UlidFactory;

final class CustomerUpdateTest extends UnitTestCase
{
    private UlidFactory $ulidFactory;
    private UlidTransformer $ulidTransformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ulidFactory = new UlidFactory();
        $ulidFactoryInterface = new UlidFactoryInterface();
        $this->ulidTransformer = new UlidTransformer(
            $ulidFactoryInterface,
            new UlidValidator(),
            new UlidTypeConverter($ulidFactoryInterface)
        );
    }

    public function testCreateCustomerUpdate(): void
    {
        $data = $this->createCustomerUpdate();
        $update = $data['update'];

        $this->assertEquals($data['initials'], $update->newInitials);
        $this->assertEquals($data['email'], $update->newEmail);
        $this->assertEquals($data['phone'], $update->newPhone);
        $this->assertEquals($data['leadSource'], $update->newLeadSource);
        $this->assertSame($data['customerType'], $update->newType);
        $this->assertSame($data['customerStatus'], $update->newStatus);
        $this->assertTrue($update->newConfirmed);
    }

    /**
     * @return array{
     *     update: CustomerUpdate,
     *     customerType: CustomerType,
     *     customerStatus: CustomerStatus,
     *     initials: string,
     *     email: string,
     *     phone: string,
     *     leadSource: string
     * }
     */
    private function createCustomerUpdate(): array
    {
        $customerType = $this->createCustomerType();
        $customerStatus = $this->createCustomerStatus();
        $customerData = $this->generateCustomerData();

        $update = new CustomerUpdate(
            newInitials: $customerData['initials'],
            newEmail: $customerData['email'],
            newPhone: $customerData['phone'],
            newLeadSource: $customerData['leadSource'],
            newType: $customerType,
            newStatus: $customerStatus,
            newConfirmed: true,
        );

        return $this->createUpdateData(
            $update,
            $customerType,
            $customerStatus,
            $customerData
        );
    }

    /**
     * @param array{
     *     initials: string,
     *     email: string,
     *     phone: string,
     *     leadSource: string
     * } $customerData
     *
     * @return array{
     *     update: CustomerUpdate,
     *     customerType: CustomerType,
     *     customerStatus: CustomerStatus,
     *     initials: string,
     *     email: string,
     *     phone: string,
     *     leadSource: string
     * }
     */
    private function createUpdateData(
        CustomerUpdate $update,
        CustomerType $customerType,
        CustomerStatus $customerStatus,
        array $customerData
    ): array {
        return [
            'update' => $update,
            'customerType' => $customerType,
            'customerStatus' => $customerStatus,
            'initials' => $customerData['initials'],
            'email' => $customerData['email'],
            'phone' => $customerData['phone'],
            'leadSource' => $customerData['leadSource'],
        ];
    }

    private function createCustomerType(): CustomerType
    {
        $typeUlid = $this->ulidTransformer
            ->transformFromSymfonyUlid($this->ulidFactory->create());

        return new CustomerType('individual', $typeUlid);
    }

    private function createCustomerStatus(): CustomerStatus
    {
        $statusUlid = $this->ulidTransformer
            ->transformFromSymfonyUlid($this->ulidFactory->create());

        return new CustomerStatus('active', $statusUlid);
    }

    /**
     * @return array{initials: string, email: string, phone: string, leadSource: string}
     */
    private function generateCustomerData(): array
    {
        return [
            'initials' => $this->faker->name(),
            'email' => $this->faker->email(),
            'phone' => $this->faker->phoneNumber(),
            'leadSource' => $this->faker->randomElement(
                ['website', 'referral', 'social']
            ),
        ];
    }
}
