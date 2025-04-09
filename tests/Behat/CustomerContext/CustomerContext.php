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

    /**
     * Prepares and persists CustomerType and CustomerStatus entities.
     * Optionally, override their values.
     *
     * @return array [CustomerType, CustomerStatus]
     */
    private function prepareCustomerEntities(
        string $id,
        ?string $typeValue = null,
        ?string $statusValue = null
    ): array {
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

        $this->trackId($id, $this->createdTypeIds);
        $this->trackId($id, $this->createdStatusIds);

        return [$type, $status];
    }

    /**
     * Creates and saves a customer.
     *
     * @param callable|null $modifyCallback Optional callback to modify the customer after creation.
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
        $this->trackId($id, $this->createdCustomerIds);
    }

    /**
     * @Given customer with id :id exists
     */
    public function customerWithIdExists(string $id): void
    {
        $this->createAndSaveCustomer(
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
        $this->createAndSaveCustomer(
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
        $this->createAndSaveCustomer(
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
        $this->createAndSaveCustomer(
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
        $this->createAndSaveCustomer(
            $id,
            $this->faker->lexify('??'),
            $this->faker->email(),
            $this->faker->e164PhoneNumber(),
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
        $this->createAndSaveCustomer(
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
    public function customerWithTypeAndStatusExists(string $typeValue, string $statusValue): void
    {
        $id = (string) $this->faker->ulid();
        $this->createAndSaveCustomer(
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
        $this->createAndSaveCustomer(
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
        $status = $this->statusRepository->find($id) ??
            $this->statusFactory->create(
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
    public function cleanupCreatedCustomersAndEntities(AfterScenarioScope $scope): void
    {
        // Clean up customers
        foreach ($this->createdCustomerIds as $id) {
            $this->deleteCustomerById($id);
        }
        $this->createdCustomerIds = [];

        // Clean up statuses
        foreach ($this->createdStatusIds as $id) {
            if ($status = $this->statusRepository->find($id)) {
                $this->statusRepository->delete($status);
            }
        }
        $this->createdStatusIds = [];

        // Clean up types
        foreach ($this->createdTypeIds as $id) {
            if ($type = $this->typeRepository->find($id)) {
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
