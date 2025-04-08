<?php

declare(strict_types=1);

namespace App\Tests\Behat\CustomerContext;

use App\Customer\Domain\Entity\CustomerStatus;
use App\Customer\Domain\Entity\CustomerType;
use App\Customer\Domain\Factory\CustomerFactoryInterface;
use App\Customer\Domain\Factory\StatusFactoryInterface;
use App\Customer\Domain\Factory\TypeFactoryInterface;
use App\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Customer\Domain\Repository\StatusRepositoryInterface;
use App\Customer\Domain\Repository\TypeRepositoryInterface;
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use App\Tests\Unit\UlidProvider;
use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\Uid\Ulid;

final class CustomerContext implements Context, SnippetAcceptingContext
{
    private Generator $faker;

    // Track created customer IDs, status IDs and now type IDs
    private array $createdCustomerIds = [];
    private array $createdStatusIds = [];
    private array $createdTypeIds = [];

    public function __construct(
        private TypeRepositoryInterface $typeRepository,
        private StatusRepositoryInterface $statusRepository,
        private UlidTransformer $ulidTransformer,
        private CustomerRepositoryInterface $customerRepository,
        private CustomerFactoryInterface $customerFactory,
        private StatusFactoryInterface $statusFactory,
        private TypeFactoryInterface $typeFactory
    ) {
        $this->faker = Factory::create();
        $this->faker->addProvider(new UlidProvider($this->faker));
    }

    /**
     * @Given customer with id :id exists
     */
    public function customerWithIdExists(string $id): void
    {
        // Generate a new type and status using new random IDs
        $type = $this->getCustomerType((string) $this->faker->ulid());
        $status = $this->getStatus((string) $this->faker->ulid());
        $this->typeRepository->save($type);
        $this->statusRepository->save($status);

        $initials = $this->faker->lexify('??');
        $email = $this->faker->email();
        $phone = $this->faker->e164PhoneNumber();
        $leadSource = $this->faker->word();
        $customer = $this->customerFactory->create(
            $initials,
            $email,
            $phone,
            $leadSource,
            $type,
            $status,
            true,
            $this->ulidTransformer->transformFromSymfonyUlid(new Ulid($id))
        );
        $this->customerRepository->save($customer);
        $this->createdCustomerIds[] = $id;
    }

    /**
     * @Given type with id :id exists
     */
    public function typeWithIdExists(string $id): void
    {
        $type = $this->getCustomerType($id);
        $this->typeRepository->save($type);
        // Record this type as created by the test
        if (!in_array($id, $this->createdTypeIds, true)) {
            $this->createdTypeIds[] = $id;
        }
    }

    /**
     * @Given status with id :id exists
     */
    public function statusWithIdExists(string $id): void
    {
        $status = $this->getStatus($id);
        $this->statusRepository->save($status);
        if (!in_array($id, $this->createdStatusIds, true)) {
            $this->createdStatusIds[] = $id;
        }
    }

    /**
     * @Given customer with initials :initials exists
     */
    public function customerWithInitialsExists(string $initials): void
    {
        $id = (string) $this->faker->ulid();
        $type = $this->getCustomerType($id);
        $status = $this->getStatus($id);
        $this->typeRepository->save($type);
        $this->statusRepository->save($status);
        // Record type and status creation
        if (!in_array($id, $this->createdTypeIds, true)) {
            $this->createdTypeIds[] = $id;
        }
        if (!in_array($id, $this->createdStatusIds, true)) {
            $this->createdStatusIds[] = $id;
        }

        $email = "customer_" . strtolower($initials) . "@example.com";
        $phone = "0123456789";
        $leadSource = "defaultSource";
        $customer = $this->customerFactory->create(
            $initials,
            $email,
            $phone,
            $leadSource,
            $type,
            $status,
            true,
            $this->ulidTransformer->transformFromSymfonyUlid(new Ulid($id))
        );
        $this->customerRepository->save($customer);
        $this->createdCustomerIds[] = $id;
    }

    /**
     * @Given customer with email :email exists
     */
    public function customerWithEmailExists(string $email): void
    {
        $id = (string) $this->faker->ulid();
        $type = $this->getCustomerType($id);
        $status = $this->getStatus($id);
        $this->typeRepository->save($type);
        $this->statusRepository->save($status);
        // Record type and status creation
        if (!in_array($id, $this->createdTypeIds, true)) {
            $this->createdTypeIds[] = $id;
        }
        if (!in_array($id, $this->createdStatusIds, true)) {
            $this->createdStatusIds[] = $id;
        }

        $initials = substr($email, 0, 2);
        $phone = "0123456789";
        $leadSource = "defaultSource";
        $customer = $this->customerFactory->create(
            $initials,
            $email,
            $phone,
            $leadSource,
            $type,
            $status,
            true,
            $this->ulidTransformer->transformFromSymfonyUlid(new Ulid($id))
        );
        $this->customerRepository->save($customer);
        $this->createdCustomerIds[] = $id;
    }

    /**
     * @Given customer with phone :phone exists
     */
    public function customerWithPhoneExists(string $phone): void
    {
        $id = (string) $this->faker->ulid();
        $type = $this->getCustomerType($id);
        $status = $this->getStatus($id);
        $this->typeRepository->save($type);
        $this->statusRepository->save($status);
        // Record type and status creation
        if (!in_array($id, $this->createdTypeIds, true)) {
            $this->createdTypeIds[] = $id;
        }
        if (!in_array($id, $this->createdStatusIds, true)) {
            $this->createdStatusIds[] = $id;
        }

        $initials = "TP";
        $email = $this->faker->email();
        $leadSource = "defaultSource";
        $customer = $this->customerFactory->create(
            $initials,
            $email,
            $phone,
            $leadSource,
            $type,
            $status,
            true,
            $this->ulidTransformer->transformFromSymfonyUlid(new Ulid($id))
        );
        $this->customerRepository->save($customer);
        $this->createdCustomerIds[] = $id;
    }

    /**
     * @Given customer with leadSource :leadSource exists
     */
    public function customerWithLeadSourceExists(string $leadSource): void
    {
        $id = (string) $this->faker->ulid();
        $type = $this->getCustomerType($id);
        $status = $this->getStatus($id);
        $this->typeRepository->save($type);
        $this->statusRepository->save($status);
        // Record type and status creation
        if (!in_array($id, $this->createdTypeIds, true)) {
            $this->createdTypeIds[] = $id;
        }
        if (!in_array($id, $this->createdStatusIds, true)) {
            $this->createdStatusIds[] = $id;
        }

        $initials = "LS";
        $email = $this->faker->email();
        $phone = "0123456789";
        $customer = $this->customerFactory->create(
            $initials,
            $email,
            $phone,
            $leadSource,
            $type,
            $status,
            true,
            $this->ulidTransformer->transformFromSymfonyUlid(new Ulid($id))
        );
        if (method_exists($customer, 'setLeadSource')) {
            $customer->setLeadSource($leadSource);
        }
        $this->customerRepository->save($customer);
        $this->createdCustomerIds[] = $id;
    }

    /**
     * @Given customer with type value "VIP" and status value "Active" and id :id exists
     */
    public function customerWithVipActiveAndIdExists(string $id): void
    {
        $type = $this->getCustomerType($id);
        $status = $this->getStatus($id);

        $type->setValue("VIP");
        $status->setValue("Active");

        $this->typeRepository->save($type);
        $this->statusRepository->save($status);
        // Record type and status creation
        if (!in_array($id, $this->createdTypeIds, true)) {
            $this->createdTypeIds[] = $id;
        }
        if (!in_array($id, $this->createdStatusIds, true)) {
            $this->createdStatusIds[] = $id;
        }

        $initials = "VIP";
        $email = $this->faker->email();
        $phone = "0123456789";
        $leadSource = "defaultSource";

        $customer = $this->customerFactory->create(
            $initials,
            $email,
            $phone,
            $leadSource,
            $type,
            $status,
            true,
            $this->ulidTransformer->transformFromSymfonyUlid(new Ulid($id))
        );

        $this->customerRepository->save($customer);
        $this->createdCustomerIds[] = $id;
    }

    /**
     * @Given customer with type value :typeValue and status value :statusValue exists
     */
    public function customerWithTypeAndStatusExists(string $typeValue, string $statusValue): void
    {
        $id = (string) $this->faker->ulid();
        $type = $this->getCustomerType($id);
        $status = $this->getStatus($id);
        $type->setValue($typeValue);
        $status->setValue($statusValue);
        $this->typeRepository->save($type);
        $this->statusRepository->save($status);
        if (!in_array($id, $this->createdTypeIds, true)) {
            $this->createdTypeIds[] = $id;
        }
        if (!in_array($id, $this->createdStatusIds, true)) {
            $this->createdStatusIds[] = $id;
        }

        $initials = "TS";
        $email = $this->faker->email();
        $phone = "0123456789";
        $leadSource = "defaultSource";
        $customer = $this->customerFactory->create(
            $initials,
            $email,
            $phone,
            $leadSource,
            $type,
            $status,
            true,
            $this->ulidTransformer->transformFromSymfonyUlid(new Ulid($id))
        );
        $this->customerRepository->save($customer);
        $this->createdCustomerIds[] = $id;
    }

    /**
     * @Given customer with confirmed :confirmed exists
     */
    public function customerWithConfirmedExists(string $confirmed): void
    {
        $id = (string) $this->faker->ulid();
        $type = $this->getCustomerType($id);
        $status = $this->getStatus($id);
        $this->typeRepository->save($type);
        $this->statusRepository->save($status);
        // Record type and status creation
        if (!in_array($id, $this->createdTypeIds, true)) {
            $this->createdTypeIds[] = $id;
        }
        if (!in_array($id, $this->createdStatusIds, true)) {
            $this->createdStatusIds[] = $id;
        }

        $initials = "CF";
        $email = $this->faker->email();
        $phone = "0123456789";
        $leadSource = "defaultSource";
        $boolConfirmed = filter_var($confirmed, FILTER_VALIDATE_BOOLEAN);
        $customer = $this->customerFactory->create(
            $initials,
            $email,
            $phone,
            $leadSource,
            $type,
            $status,
            $boolConfirmed,
            $this->ulidTransformer->transformFromSymfonyUlid(new Ulid($id))
        );
        $this->customerRepository->save($customer);
        $this->createdCustomerIds[] = $id;
    }

    /**
     * @Given customer status with value :value exists
     */
    public function customerStatusWithValueExists(string $value): void
    {
        $id = (string) $this->faker->ulid();

        $status = $this->statusRepository->find($id);
        if ($status === null) {
            $status = $this->statusFactory->create(
                $value,
                $this->ulidTransformer->transformFromSymfonyUlid(new Ulid($id))
            );
            $this->createdStatusIds[] = $id;
        } else {
            $status->setValue($value);
        }
        $this->statusRepository->save($status);
    }

    /**
     * @Given customer type with value :value exists
     */
    public function customerTypeWithValueExists(string $value): void
    {
        $id = (string) $this->faker->ulid();
        $type = $this->getCustomerType($id);
        $type->setValue($value);
        $this->typeRepository->save($type);
        if (!in_array($id, $this->createdTypeIds, true)) {
            $this->createdTypeIds[] = $id;
        }
    }

    /**
     * Helper method to get or create a CustomerType.
     */
    public function getCustomerType(string $id): CustomerType
    {
        $type = $this->typeRepository->find($id);
        if ($type === null) {
            $type = $this->typeFactory->create(
                $this->faker->word(),
                $this->ulidTransformer->transformFromSymfonyUlid(new Ulid($id))
            );
            $this->createdTypeIds[] = $id;
        }
        return $type;
    }

    /**
     * Helper method to get or create a CustomerStatus.
     */
    public function getStatus(string $id): CustomerStatus
    {
        $status = $this->statusRepository->find($id);
        if ($status === null) {
            $status = $this->statusFactory->create(
                $this->faker->word(),
                $this->ulidTransformer->transformFromSymfonyUlid(new Ulid($id))
            );
            $this->createdStatusIds[] = $id;
        }
        return $status;
    }

    /**
     * @AfterScenario
     */
    public function cleanupCreatedCustomersAndEntities(AfterScenarioScope $scope): void
    {
        // First, delete all created customers
        foreach ($this->createdCustomerIds as $id) {
            $this->deleteCustomerById($id);
        }
        $this->createdCustomerIds = [];

        // Then, delete all created statuses
        foreach ($this->createdStatusIds as $id) {
            $status = $this->statusRepository->find($id);
            if ($status !== null) {
                $this->statusRepository->delete($status);
            }
        }
        $this->createdStatusIds = [];

        // Finally, delete all created customer types
        foreach ($this->createdTypeIds as $id) {
            $type = $this->typeRepository->find($id);
            if ($type !== null) {
                $this->typeRepository->delete($type);
            }
        }
        $this->createdTypeIds = [];
    }

    /**
     * @Then delete customer with id :id
     */
    public function deleteCustomerById(mixed $id): void
    {
        $customer = $this->customerRepository->find($id);
        if ($customer !== null) {
            $this->customerRepository->delete($customer);
        }
    }

    /**
     * @Then delete customer with email :email
     */
    public function deleteCustomerByEmail(string $email): void
    {
        $customer = $this->customerRepository->findByEmail($email);
        if ($customer !== null) {
            $this->customerRepository->delete($customer);
        }
    }

    /**
     * @Then delete status with value :value
     */
    public function deleteStatusByValue(string $value): void
    {
        $this->statusRepository->deleteByValue($value);
    }

    /**
     * @Then delete type with value :value
     */
    public function deleteTypeByValue(string $value): void
    {
        $this->typeRepository->deleteByValue($value);
    }
}
