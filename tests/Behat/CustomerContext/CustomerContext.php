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
    }

    /**
     * @Given status with id :id exists
     */
    public function statusWithIdExists(string $id): void
    {
        $status = $this->getStatus($id);
        $this->statusRepository->save($status);
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

        $initials = "TP";
        $email = "customer_" . preg_replace('/\D/', '', $phone) . "@example.com";
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

        $initials = "LS";
        $email = "customer_" . strtolower($leadSource) . "@example.com";
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
        // Retrieve or create a customer type and status using the provided id.
        $type = $this->getCustomerType($id);
        $status = $this->getStatus($id);

        // Set the desired values.
        $type->setValue("VIP");
        $status->setValue("Active");

        // Save these entities so they exist in the database.
        $this->typeRepository->save($type);
        $this->statusRepository->save($status);

        // Create the customer with fixed initials, email, etc.
        $initials = "VIP";
        $email = "vip.active@example.com";
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

        // Save the customer and store the id for later cleanup.
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

        $initials = "TS";
        $email = "customer_" . strtolower($typeValue) . "_" . strtolower($statusValue) . "@example.com";
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

    public function getCustomerType(string $id): CustomerType
    {
        return $this->typeRepository->find($id) ??
            $this->typeFactory->create(
                $this->faker->word(),
                $this->ulidTransformer->transformFromSymfonyUlid(new Ulid($id))
            );
    }

    public function getStatus(string $id): CustomerStatus
    {
        return $this->statusRepository->find($id) ??
            $this->statusFactory->create(
                $this->faker->word(),
                $this->ulidTransformer->transformFromSymfonyUlid(new Ulid($id))
            );
    }

    /**
     * @AfterScenario
     */
    public function cleanupCreatedCustomers(AfterScenarioScope $scope): void
    {
        foreach ($this->createdCustomerIds as $id) {
            $this->deleteCustomerById($id);
        }
        $this->createdCustomerIds = [];
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
}
