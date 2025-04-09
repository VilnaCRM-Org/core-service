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
     * Generic helper to track created IDs.
     */
    private function trackId(string $id, array &$storage): void
    {
        if (!in_array($id, $storage, true)) {
            $storage[] = $id;
        }
    }

    /* ============================================================
       PREPARE ENTITIES (CustomerType and CustomerStatus)
       Two versions are provided.
    ============================================================ */

    /**
     * Prepares and persists CustomerType and CustomerStatus entities
     * using default (faker) values.
     *
     * @return array [CustomerType, CustomerStatus]
     */
    private function prepareCustomerEntitiesDefault(string $id): array
    {
        $type = $this->getCustomerType($id);
        $status = $this->getStatus($id);

        // Always use the default (faker) word.
        $type->setValue($this->faker->word());
        $status->setValue($this->faker->word());

        $this->typeRepository->save($type);
        $this->statusRepository->save($status);

        $this->trackId($id, $this->createdTypeIds);
        $this->trackId($id, $this->createdStatusIds);

        return [$type, $status];
    }

    /**
     * Prepares and persists CustomerType and CustomerStatus entities
     * using provided values.
     *
     * @return array [CustomerType, CustomerStatus]
     */
    private function prepareCustomerWithValues(
        string $id,
        string $typeValue,
        string $statusValue
    ): array {
        $type = $this->getCustomerType($id);
        $status = $this->getStatus($id);

        // Directly set the provided values.
        $type->setValue($typeValue);
        $status->setValue($statusValue);

        $this->typeRepository->save($type);
        $this->statusRepository->save($status);

        $this->trackId($id, $this->createdTypeIds);
        $this->trackId($id, $this->createdStatusIds);

        return [$type, $status];
    }

    /* ============================================================
       CREATE AND SAVE CUSTOMER METHODS
       Three variants: default, with provided type/status,
       and one that always sets a specific lead source.
    ============================================================ */

    /**
     * Creates and saves a customer using default entity values.
     */
    private function createAndSaveCustomerDefault(
        string $id,
        string $initials,
        string $email,
        string $phone,
        string $leadSource,
        bool $confirmed = true
    ): void {
        [$type, $status] = $this->prepareCustomerEntitiesDefault($id);
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
        $this->customerRepository->save($customer);
        $this->trackId($id, $this->createdCustomerIds);
    }

    /**
     * Creates and saves a customer using provided type and status values.
     */
    private function createAndSaveCustomerWithValues(
        string $id,
        string $initials,
        string $email,
        string $phone,
        string $leadSource,
        bool $confirmed,
        string $typeValue,
        string $statusValue
    ): void {
        [$type, $status] = $this->prepareCustomerWithValues(
            $id,
            $typeValue,
            $statusValue
        );
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
        $this->customerRepository->save($customer);
        $this->trackId($id, $this->createdCustomerIds);
    }

    /**
     * Creates and saves a customer then sets a specific lead source.
     * (This duplicates the customer creation logic so that no conditional callback is used.)
     */
    private function createAndSaveCustomerWithLeadSource(
        string $id,
        string $initials,
        string $email,
        string $phone,
        string $leadSource
    ): void {
        [$type, $status] = $this->prepareCustomerEntitiesDefault($id);
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
        // Explicitly set the lead source (duplication rather than a callback)
        $customer->setLeadSource($leadSource);
        $this->customerRepository->save($customer);
        $this->trackId($id, $this->createdCustomerIds);
    }

    /* ============================================================
       STEP DEFINITIONS
       Each step now calls the appropriate duplicated method.
    ============================================================ */

    /**
     * @Given customer with id :id exists
     */
    public function customerWithIdExists(string $id): void
    {
        $this->createAndSaveCustomerDefault(
            $id,
            $this->faker->lexify('??'),
            $this->faker->email(),
            $this->faker->e164PhoneNumber(),
            $this->faker->word()
        );
    }

    /**
     * @Given type with id :id exists
     */
    public function typeWithIdExists(string $id): void
    {
        $type = $this->getCustomerType($id);
        $this->typeRepository->save($type);
        $this->trackId($id, $this->createdTypeIds);
    }

    /**
     * @Given status with id :id exists
     */
    public function statusWithIdExists(string $id): void
    {
        $status = $this->getStatus($id);
        $this->statusRepository->save($status);
        $this->trackId($id, $this->createdStatusIds);
    }

    /**
     * @Given customer with initials :initials exists
     */
    public function customerWithInitialsExists(string $initials): void
    {
        $id = (string) $this->faker->ulid();
        $this->createAndSaveCustomerDefault(
            $id,
            $initials,
            $this->faker->email(),
            $this->faker->e164PhoneNumber(),
            $this->faker->word()
        );
    }

    /**
     * @Given customer with email :email exists
     */
    public function customerWithEmailExists(string $email): void
    {
        $id = (string) $this->faker->ulid();
        $this->createAndSaveCustomerDefault(
            $id,
            $this->faker->lexify('??'),
            $email,
            $this->faker->e164PhoneNumber(),
            'defaultSource'
        );
    }

    /**
     * @Given customer with phone :phone exists
     */
    public function customerWithPhoneExists(string $phone): void
    {
        $id = (string) $this->faker->ulid();
        $this->createAndSaveCustomerDefault(
            $id,
            $this->faker->lexify('??'),
            $this->faker->email(),
            $phone,
            $this->faker->word()
        );
    }

    /**
     * @Given customer with leadSource :leadSource exists
     */
    public function customerWithLeadSourceExists(string $leadSource): void
    {
        $id = (string) $this->faker->ulid();
        $this->createAndSaveCustomerWithLeadSource(
            $id,
            $this->faker->lexify('??'),
            $this->faker->email(),
            $this->faker->e164PhoneNumber(),
            $leadSource
        );
    }

    /**
     * @Given customer with type value "VIP" and status value "Active" and id :id exists
     */
    public function customerWithVipActiveAndIdExists(string $id): void
    {
        $this->createAndSaveCustomerWithValues(
            $id,
            $this->faker->lexify('??'),
            $this->faker->email(),
            $this->faker->e164PhoneNumber(),
            $this->faker->word(),
            true,
            'VIP',
            'Active'
        );
    }

    /**
     * @Given customer with type value :typeValue and status value :statusValue exists
     */
    public function customerWithTypeAndStatusExists(
        string $typeValue,
        string $statusValue
    ): void {
        $id = (string) $this->faker->ulid();
        $this->createAndSaveCustomerWithValues(
            $id,
            $this->faker->lexify('??'),
            $this->faker->email(),
            $this->faker->e164PhoneNumber(),
            $this->faker->word(),
            true,
            $typeValue,
            $statusValue
        );
    }

    /**
     * @Given customer with confirmed :confirmed exists
     */
    public function customerWithConfirmedExists(string $confirmed): void
    {
        $id = (string) $this->faker->ulid();
        $this->createAndSaveCustomerDefault(
            $id,
            $this->faker->lexify('??'),
            $this->faker->email(),
            $this->faker->e164PhoneNumber(),
            $this->faker->word(),
            filter_var($confirmed, FILTER_VALIDATE_BOOLEAN)
        );
    }

    /**
     * @Given customer status with value :value exists
     */
    public function customerStatusWithValueExists(string $value): void
    {
        $id = (string) $this->faker->ulid();
        $status = $this->statusRepository->find($id)
            ?? $this->statusFactory->create(
                $value,
                $this->ulidTransformer->transformFromSymfonyUlid(new Ulid($id))
            );
        $status->setValue($value);
        $this->statusRepository->save($status);
        $this->trackId($id, $this->createdStatusIds);
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
        $this->trackId($id, $this->createdTypeIds);
    }

    /**
     * Helper method to retrieve or create a CustomerType.
     */
    public function getCustomerType(string $id): CustomerType
    {
        $type = $this->typeFactory->create(
            $this->faker->word(),
            $this->ulidTransformer->transformFromSymfonyUlid(new Ulid($id))
        );
        $this->trackId($id, $this->createdTypeIds);
        return $type;
    }

    /**
     * Helper method to retrieve or create a CustomerStatus.
     */
    public function getStatus(string $id): CustomerStatus
    {
        $status = $this->statusFactory->create(
            $this->faker->word(),
            $this->ulidTransformer->transformFromSymfonyUlid(new Ulid($id))
        );
        $this->trackId($id, $this->createdStatusIds);
        return $status;
    }

    /**
     * Cleanup after each scenario.
     *
     * @AfterScenario
     */
    public function cleanupCreatedCustomersAndEntities(
        AfterScenarioScope $scope
    ): void {
        foreach ($this->createdCustomerIds as $id) {
            $this->deleteCustomerById($id);
        }
        $this->createdCustomerIds = [];

        // Clean up statuses.
        foreach ($this->createdStatusIds as $id) {
            $status = $this->statusRepository->find($id);
            $this->statusRepository->delete($status);
        }
        $this->createdStatusIds = [];

        // Clean up types.
        foreach ($this->createdTypeIds as $id) {
            $type = $this->typeRepository->find($id);
            $this->typeRepository->delete($type);
        }
        $this->createdTypeIds = [];
    }

    /**
     * @Then delete customer with id :id
     */
    public function deleteCustomerById(mixed $id): void
    {
        $customer = $this->customerRepository->find($id);
        $this->customerRepository->delete($customer);
    }

    /**
     * @Then delete customer with email :email
     */
    public function deleteCustomerByEmail(string $email): void
    {
        $customer = $this->customerRepository->findByEmail($email);
        $this->customerRepository->delete($customer);
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
