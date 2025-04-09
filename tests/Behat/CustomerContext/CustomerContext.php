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
     * Helper method to prepare and persist CustomerType and CustomerStatus entities.
     * Optionally sets their values if overrides are provided.
     *
     * @param string      $id          The identifier used for both entities.
     * @param string|null $typeValue   Optional value override for the customer type.
     * @param string|null $statusValue Optional value override for the customer status.
     *
     * @return array An array containing the CustomerType and CustomerStatus objects.
     */
    private function prepareCustomerEntities(string $id, ?string $typeValue = null, ?string $statusValue = null): array
    {
        $type = $this->getCustomerType($id);
        $status = $this->getStatus($id);

        if ($typeValue !== null) {
            $type->setValue($typeValue);
        }
        if ($statusValue !== null) {
            $status->setValue($statusValue);
        }

        $this->typeRepository->save($type);
        $this->statusRepository->save($status);

        if (!in_array($id, $this->createdTypeIds, true)) {
            $this->createdTypeIds[] = $id;
        }
        if (!in_array($id, $this->createdStatusIds, true)) {
            $this->createdStatusIds[] = $id;
        }

        return [$type, $status];
    }

    /**
     * Helper method to create and save a customer.
     * It encapsulates entity preparation, customer creation, optional modification, and saving.
     *
     * @param string        $id             The customer ID.
     * @param string        $initials       The customer's initials.
     * @param string        $email          The customer's email.
     * @param string        $phone          The customer's phone number.
     * @param string        $leadSource     The customer's lead source.
     * @param bool          $confirmed      Whether the customer is confirmed.
     * @param string|null   $typeValue      Optional type value override.
     * @param string|null   $statusValue    Optional status value override.
     * @param callable|null $modifyCallback Optional callback for modifying the customer instance.
     */
    private function createAndSaveCustomer(
        string $id,
        string $initials,
        string $email,
        string $phone,
        string $leadSource,
        bool $confirmed = true,
        ?string $typeValue = null,
        ?string $statusValue = null,
        ?callable $modifyCallback = null
    ): void {
        [$type, $status] = $this->prepareCustomerEntities($id, $typeValue, $statusValue);

        $customer = $this->customerFactory->create(
            $initials,
            $email,
            $phone,
            $leadSource,
            $type,
            $status,
            $confirmed,
            $this->ulidTransformer->transformFromSymfonyUlid(new Ulid($id))
        );

        if ($modifyCallback !== null) {
            $modifyCallback($customer);
        }

        $this->customerRepository->save($customer);
        $this->createdCustomerIds[] = $id;
    }

    /**
     * @Given customer with id :id exists
     */
    public function customerWithIdExists(string $id): void
    {
        $initials = $this->faker->lexify('??');
        $email = $this->faker->email();
        $phone = $this->faker->e164PhoneNumber();
        $leadSource = $this->faker->word();

        $this->createAndSaveCustomer($id, $initials, $email, $phone, $leadSource);
    }

    /**
     * @Given type with id :id exists
     */
    public function typeWithIdExists(string $id): void
    {
        $type = $this->getCustomerType($id);
        $this->typeRepository->save($type);

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
        $email = $this->faker->email();
        $phone = $this->faker->e164PhoneNumber();
        $leadSource = $this->faker->word();

        $this->createAndSaveCustomer($id, $initials, $email, $phone, $leadSource);
    }

    /**
     * @Given customer with email :email exists
     */
    public function customerWithEmailExists(string $email): void
    {
        $id = (string) $this->faker->ulid();
        $initials = $this->faker->lexify('??');
        $phone = $this->faker->e164PhoneNumber();
        $leadSource = 'defaultSource';

        $this->createAndSaveCustomer($id, $initials, $email, $phone, $leadSource);
    }

    /**
     * @Given customer with phone :phone exists
     */
    public function customerWithPhoneExists(string $phone): void
    {
        $id = (string) $this->faker->ulid();
        $initials = $this->faker->lexify('??');
        $email = $this->faker->email();
        $leadSource = $this->faker->word();

        $this->createAndSaveCustomer($id, $initials, $email, $phone, $leadSource);
    }

    /**
     * @Given customer with leadSource :leadSource exists
     */
    public function customerWithLeadSourceExists(string $leadSource): void
    {
        $id = (string) $this->faker->ulid();
        $initials = $this->faker->lexify('??');
        $email = $this->faker->email();
        $phone = $this->faker->e164PhoneNumber();

        $this->createAndSaveCustomer(
            $id,
            $initials,
            $email,
            $phone,
            $leadSource,
            true,
            null,
            null,
            function ($customer) use ($leadSource) {
                $customer->setLeadSource($leadSource);
            }
        );
    }

    /**
     * @Given customer with type value "VIP" and status value "Active" and id :id exists
     */
    public function customerWithVipActiveAndIdExists(string $id): void
    {
        $initials = $this->faker->lexify('??');
        $email = $this->faker->email();
        $phone = $this->faker->e164PhoneNumber();
        $leadSource = $this->faker->word();

        $this->createAndSaveCustomer($id, $initials, $email, $phone, $leadSource, true, 'VIP', 'Active');
    }

    /**
     * @Given customer with type value :typeValue and status value :statusValue exists
     */
    public function customerWithTypeAndStatusExists(string $typeValue, string $statusValue): void
    {
        $id = (string) $this->faker->ulid();
        $initials = $this->faker->lexify('??');
        $email = $this->faker->email();
        $phone = $this->faker->e164PhoneNumber();
        $leadSource = $this->faker->word();

        $this->createAndSaveCustomer($id, $initials, $email, $phone, $leadSource, true, $typeValue, $statusValue);
    }

    /**
     * @Given customer with confirmed :confirmed exists
     */
    public function customerWithConfirmedExists(string $confirmed): void
    {
        $id = (string) $this->faker->ulid();
        $initials = $this->faker->lexify('??');
        $email = $this->faker->email();
        $phone = $this->faker->e164PhoneNumber();
        $leadSource = $this->faker->word();
        $boolConfirmed = filter_var($confirmed, FILTER_VALIDATE_BOOLEAN);

        $this->createAndSaveCustomer($id, $initials, $email, $phone, $leadSource, $boolConfirmed);
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
        // Delete all created customers
        foreach ($this->createdCustomerIds as $id) {
            $this->deleteCustomerById($id);
        }
        $this->createdCustomerIds = [];

        // Delete all created statuses
        foreach ($this->createdStatusIds as $id) {
            $status = $this->statusRepository->find($id);
            if ($status !== null) {
                $this->statusRepository->delete($status);
            }
        }
        $this->createdStatusIds = [];

        // Delete all created customer types
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
