<?php

declare(strict_types=1);

namespace App\Tests\Behat\CustomerContext;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Factory\CustomerFactoryInterface;
use App\Core\Customer\Domain\Factory\StatusFactoryInterface;
use App\Core\Customer\Domain\Factory\TypeFactoryInterface;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Core\Customer\Domain\Repository\StatusRepositoryInterface;
use App\Core\Customer\Domain\Repository\TypeRepositoryInterface;
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use App\Tests\Unit\UlidProvider;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use DateInterval;
use DateTime;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\Uid\Ulid;
use TwentytwoLabs\BehatOpenApiExtension\Context\RestContext;

/**
 * @psalm-suppress UnusedClass
 */
final class CustomerContext implements Context
{
    /** @var array<string> */
    private array $createdCustomerIds = [];
    /** @var array<string> */
    private array $createdStatusIds = [];
    /** @var array<string> */
    private array $createdTypeIds = [];

    private Generator $faker;
    private RestContext $restContext;

    public function __construct(
        private TypeRepositoryInterface $typeRepository,
        private StatusRepositoryInterface $statusRepository,
        private UlidTransformer $ulidTransformer,
        private CustomerRepositoryInterface $customerRepository,
        private CustomerFactoryInterface $customerFactory,
        private StatusFactoryInterface $statusFactory,
        private TypeFactoryInterface $typeFactory,
    ) {
        $this->initializeFaker();
    }

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope): void
    {
        $environment = $scope->getEnvironment();
        $this->restContext = $environment->getContext(RestContext::class);
    }

    /**
     * @When I send a GET data request to :url
     */
    public function iSendAGetDataRequestTo(string $url): void
    {
        $mappedUrl = $this->mapDynamicDates($url);
        $fullUrl = $this->buildFullUrl($mappedUrl);
        $this->restContext->iSendARequestTo('GET', $fullUrl);
    }

    /**
     * @Given create customer with id :id
     */
    public function customerWithIdExists(string $id): void
    {
        $this->createCustomerWithDefaults($id);
    }

    /**
     * @Given create type with id :id
     */
    public function typeWithIdExists(string $id): void
    {
        $this->createAndSaveType($id);
    }

    /**
     * @Given create status with id :id
     */
    public function statusWithIdExists(string $id): void
    {
        $this->createAndSaveStatus($id);
    }

    /**
     * @Given create customer with initials :initials
     */
    public function customerWithInitialsExists(string $initials): void
    {
        $data = ['initials' => $initials];
        $this->createCustomerWithCustomData($data);
    }

    /**
     * @Given create customer with email :email
     */
    public function customerWithEmailExists(string $email): void
    {
        $data = $this->buildEmailCustomerData($email);
        $this->createCustomerWithCustomData($data);
    }

    /**
     * @Given create customer with phone :phone
     */
    public function customerWithPhoneExists(string $phone): void
    {
        $data = ['phone' => $phone];
        $this->createCustomerWithCustomData($data);
    }

    /**
     * @Given create customer with leadSource :leadSource
     */
    public function customerWithLeadSourceExists(string $leadSource): void
    {
        $this->createCustomerWithSpecificLeadSource($leadSource);
    }

    /**
     * @Given create customer with type value :type and status value :status and id :id
     */
    public function customerWithTypeStatusAndIdExists(
        string $type,
        string $status,
        string $id
    ): void {
        $this->createCustomerWithSpecificTypeAndStatus($type, $status, $id);
    }

    /**
     * @Given customer with type value :typeValue and status value :statusValue exists
     */
    public function customerWithTypeAndStatusExists(
        string $typeValue,
        string $statusValue
    ): void {
        $this->createCustomerWithRandomIdAndTypeStatus(
            $typeValue,
            $statusValue
        );
    }

    /**
     * @Given create customer with confirmed :confirmed
     */
    public function customerWithConfirmedExists(string $confirmed): void
    {
        $confirmedValue = filter_var($confirmed, FILTER_VALIDATE_BOOLEAN);
        $data = ['confirmed' => $confirmedValue];
        $this->createCustomerWithCustomData($data);
    }

    /**
     * @Given create customer status with value :value
     */
    public function customerStatusWithValueExists(string $value): void
    {
        $id = (string) $this->faker->ulid();
        $this->createCustomStatus($id, $value);
    }

    /**
     * @Given create customer type with value :value
     */
    public function customerTypeWithValueExists(string $value): void
    {
        $id = (string) $this->faker->ulid();
        $this->createCustomType($id, $value);
    }

    /**
     * @AfterScenario
     * @psalm-suppress UnusedParam
     */
    public function cleanupCreatedCustomersAndEntities(
        AfterScenarioScope $scope
    ): void {
        $this->safeCleanup();
    }

    /**
     * @Then delete customer with id :id
     */
    public function deleteCustomerById(string $id): void
    {
        $this->safeDeleteCustomerById($id);
    }

    /**
     * @Then delete customer with email :email
     */
    public function deleteCustomerByEmail(string $email): void
    {
        $this->safeDeleteCustomerByEmail($email);
    }

    /**
     * @Then delete status with value :value
     */
    public function deleteStatusByValue(string $value): void
    {
        $this->safeDeleteStatusByValue($value);
    }

    /**
     * @Then delete type with value :value
     */
    public function deleteTypeByValue(string $value): void
    {
        $this->safeDeleteTypeByValue($value);
    }

    private function initializeFaker(): void
    {
        $this->faker = Factory::create();
        $this->faker->addProvider(new UlidProvider($this->faker));
    }

    private function buildFullUrl(string $mappedUrl): string
    {
        return getenv('BASE_URL') . $mappedUrl;
    }

    private function createCustomerWithDefaults(string $id): void
    {
        $type = $this->createAndSaveType($id);
        $status = $this->createAndSaveStatus($id);
        $data = $this->getDefaultCustomerData();
        $this->createAndSaveCustomer($id, $data, $type, $status);
    }

    /**
     * @return array<string, string>
     */
    private function buildEmailCustomerData(string $email): array
    {
        return [
            'email' => $email,
            'leadSource' => 'defaultSource',
        ];
    }

    private function createCustomerWithSpecificLeadSource(
        string $leadSource
    ): void {
        $id = (string) $this->faker->ulid();
        $type = $this->createAndSaveType($id);
        $status = $this->createAndSaveStatus($id);
        $data = $this->getDefaultCustomerData();
        $data['leadSource'] = $leadSource;

        $customer = $this->buildCustomer($id, $data, $type, $status);
        $customer->setLeadSource($leadSource);
        $this->saveCustomerAndTrack($customer, $id);
    }

    private function createCustomerWithSpecificTypeAndStatus(
        string $typeValue,
        string $statusValue,
        string $id
    ): void {
        $typeEntity = $this->createCustomType($id, $typeValue);
        $statusEntity = $this->createCustomStatus($id, $statusValue);
        $data = $this->getDefaultCustomerData();
        $this->createAndSaveCustomer($id, $data, $typeEntity, $statusEntity);
    }

    private function createCustomerWithRandomIdAndTypeStatus(
        string $typeValue,
        string $statusValue
    ): void {
        $id = (string) $this->faker->ulid();
        $this->createCustomerWithSpecificTypeAndStatus(
            $typeValue,
            $statusValue,
            $id
        );
    }

    private function safeCleanup(): void
    {
        try {
            $this->performCleanup();
        } catch (\Throwable $e) {
            error_log('Cleanup failed: ' . $e->getMessage());
        }
    }

    private function safeDeleteCustomerById(string $id): void
    {
        try {
            $customer = $this->customerRepository->find($id);
            if ($customer) {
                $this->customerRepository->delete($customer);
            }
        } catch (\Throwable $e) {
            $this->logError('Failed to delete customer', $id, $e);
        }
    }

    private function safeDeleteCustomerByEmail(string $email): void
    {
        try {
            $customer = $this->customerRepository->findByEmail($email);
            if ($customer) {
                $this->customerRepository->delete($customer);
            }
        } catch (\Throwable $e) {
            $this->logError('Failed to delete customer', $email, $e);
        }
    }

    private function safeDeleteStatusByValue(string $value): void
    {
        try {
            $this->statusRepository->deleteByValue($value);
        } catch (\Throwable $e) {
            $this->logError('Failed to delete status', $value, $e);
        }
    }

    private function safeDeleteTypeByValue(string $value): void
    {
        try {
            $this->typeRepository->deleteByValue($value);
        } catch (\Throwable $e) {
            $this->logError('Failed to delete type', $value, $e);
        }
    }

    private function logError(
        string $message,
        string $identifier,
        \Throwable $e
    ): void {
        error_log(sprintf(
            '%s %s: %s',
            $message,
            $identifier,
            $e->getMessage()
        ));
    }

    /**
     * @param array<string, string|bool> $customData
     */
    private function createCustomerWithCustomData(array $customData): void
    {
        $id = (string) $this->faker->ulid();
        $type = $this->createAndSaveType($id);
        $status = $this->createAndSaveStatus($id);
        $data = array_merge($this->getDefaultCustomerData(), $customData);
        $this->createAndSaveCustomer($id, $data, $type, $status);
    }

    private function saveCustomerAndTrack(Customer $customer, string $id): void
    {
        $this->customerRepository->save($customer);
        $this->createdCustomerIds[] = $id;
    }

    private function performCleanup(): void
    {
        $this->cleanupCustomers();
        $this->cleanupStatuses();
        $this->cleanupTypes();
        $this->resetIdArrays();
    }

    private function cleanupCustomers(): void
    {
        foreach ($this->createdCustomerIds as $id) {
            $this->deleteCustomerById($id);
        }
    }

    private function cleanupStatuses(): void
    {
        foreach ($this->createdStatusIds as $id) {
            $this->deleteStatusEntity($id);
        }
    }

    private function cleanupTypes(): void
    {
        foreach ($this->createdTypeIds as $id) {
            $this->deleteTypeEntity($id);
        }
    }

    private function resetIdArrays(): void
    {
        $this->createdCustomerIds = [];
        $this->createdStatusIds = [];
        $this->createdTypeIds = [];
    }

    private function createAndSaveType(string $id): CustomerType
    {
        $type = $this->createType($id);
        $type->setValue($this->faker->word());
        $this->typeRepository->save($type);
        $this->createdTypeIds[] = $id;
        return $type;
    }

    private function createAndSaveStatus(string $id): CustomerStatus
    {
        $status = $this->createStatus($id);
        $status->setValue($this->faker->word());
        $this->statusRepository->save($status);
        $this->createdStatusIds[] = $id;
        return $status;
    }

    private function createCustomType(string $id, string $value): CustomerType
    {
        $type = $this->createType($id);
        $type->setValue($value);
        $this->typeRepository->save($type);
        $this->createdTypeIds[] = $id;
        return $type;
    }

    private function createCustomStatus(
        string $id,
        string $value
    ): CustomerStatus {
        $status = $this->createStatus($id);
        $status->setValue($value);
        $this->statusRepository->save($status);
        $this->createdStatusIds[] = $id;
        return $status;
    }

    private function createType(string $id): CustomerType
    {
        $ulid = $this->createUlidFromString($id);
        $word = $this->faker->word();
        return $this->typeFactory->create($word, $ulid);
    }

    private function createStatus(string $id): CustomerStatus
    {
        $ulid = $this->createUlidFromString($id);
        $word = $this->faker->word();
        return $this->statusFactory->create($word, $ulid);
    }

    /**
     * @return array<string, string|bool>
     */
    private function getDefaultCustomerData(): array
    {
        return [
            'initials' => $this->faker->lexify('??'),
            'email' => $this->faker->email(),
            'phone' => $this->faker->e164PhoneNumber(),
            'leadSource' => $this->faker->word(),
            'confirmed' => true,
        ];
    }

    /**
     * @param array<string, string|bool> $data
     */
    private function createAndSaveCustomer(
        string $id,
        array $data,
        CustomerType $type,
        CustomerStatus $status
    ): void {
        $customer = $this->buildCustomer($id, $data, $type, $status);
        $this->saveCustomerAndTrack($customer, $id);
    }

    /**
     * @param array<string, string|bool> $data
     */
    private function buildCustomer(
        string $id,
        array $data,
        CustomerType $type,
        CustomerStatus $status
    ): Customer {
        $ulid = $this->createUlidFromString($id);
        return $this->customerFactory->create(
            (string) $data['initials'],
            (string) $data['email'],
            (string) $data['phone'],
            (string) $data['leadSource'],
            $type,
            $status,
            (bool) $data['confirmed'],
            $ulid
        );
    }

    private function mapDynamicDates(string $url): string
    {
        $pattern = '/!%date\((.*?)\),date_interval\((.*?)\)!%/';
        return preg_replace_callback(
            $pattern,
            fn ($matches) => $this->replaceDatePlaceholder($matches),
            $url
        ) ?? $url;
    }

    /**
     * @param array<string> $matches
     */
    private function replaceDatePlaceholder(array $matches): string
    {
        $date = $this->createDateWithInterval($matches[2]);
        return $date->format($matches[1]);
    }

    private function createDateWithInterval(string $interval): DateTime
    {
        $date = new DateTime();
        $this->applyDateInterval($date, $interval);
        return $date;
    }

    private function applyDateInterval(DateTime $date, string $interval): void
    {
        $isNegative = str_starts_with($interval, '-');
        $cleanInterval = $isNegative ? substr($interval, 1) : $interval;
        $dateInterval = new DateInterval($cleanInterval);

        $isNegative ? $date->sub($dateInterval) : $date->add($dateInterval);
    }

    private function deleteStatusEntity(string $id): void
    {
        try {
            $status = $this->statusRepository->find($id);
            if ($status) {
                $this->statusRepository->delete($status);
            }
        } catch (\Throwable $e) {
            $this->logError('Failed to delete status entity', $id, $e);
        }
    }

    private function deleteTypeEntity(string $id): void
    {
        try {
            $type = $this->typeRepository->find($id);
            if ($type) {
                $this->typeRepository->delete($type);
            }
        } catch (\Throwable $e) {
            $this->logError('Failed to delete type entity', $id, $e);
        }
    }

    private function createUlidFromString(string $id): object
    {
        $symfonyUlid = new Ulid($id);
        return $this->ulidTransformer->transformFromSymfonyUlid($symfonyUlid);
    }
}
