<?php

declare(strict_types=1);

namespace App\Tests\Behat\CustomerContext;

use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Factory\CustomerFactoryInterface;
use App\Core\Customer\Domain\Factory\StatusFactoryInterface;
use App\Core\Customer\Domain\Factory\TypeFactoryInterface;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Core\Customer\Domain\Repository\StatusRepositoryInterface;
use App\Core\Customer\Domain\Repository\TypeRepositoryInterface;
use App\Shared\Infrastructure\Factory\UlidFactory;
use App\Tests\Unit\UlidProvider;
use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use DateInterval;
use DateTime;
use Faker\Factory;
use Faker\Generator;
use TwentytwoLabs\BehatOpenApiExtension\Context\RestContext;

/**
 * @psalm-suppress UndefinedClass
 */
final class CustomerContext implements Context, SnippetAcceptingContext
{
    /** @var array<string> */
    private array $createdCustomerIds = [];
    /** @var array<string> */
    private array $createdStatusIds = [];
    /** @var array<string> */
    private array $createdTypeIds = [];

    private Generator $faker;

    /** @psalm-suppress UndefinedClass */
    private RestContext $restContext;

    public function __construct(
        private TypeRepositoryInterface $typeRepository,
        private StatusRepositoryInterface $statusRepository,
        private UlidFactory $ulidFactory,
        private CustomerRepositoryInterface $customerRepository,
        private CustomerFactoryInterface $customerFactory,
        private StatusFactoryInterface $statusFactory,
        private TypeFactoryInterface $typeFactory,
    ) {
        $this->faker = Factory::create();
        $this->faker->addProvider(new UlidProvider($this->faker));
    }

    /**
     * @BeforeScenario
     *
     * @psalm-suppress UndefinedClass
     */
    public function gatherContexts(BeforeScenarioScope $scope): void
    {
        $environment = $scope->getEnvironment();
        $this->restContext = $environment->getContext(RestContext::class);
    }

    /**
     * @When I send a GET data request to :url
     *
     * @psalm-suppress UndefinedClass
     */
    public function iSendAGetDataRequestTo(string $url): void
    {
        $mappedUrl = $this->mapDynamicDates($url);
        $mappedUrl = getenv('BASE_URL') . $mappedUrl;
        $this->restContext->iSendARequestTo('GET', $mappedUrl);
    }

    /**
     * @Given create customer with id :id
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
     * @Given create type with id :id
     */
    public function typeWithIdExists(string $id): void
    {
        $type = $this->getCustomerType($id);
        $this->typeRepository->save($type);
        $this->trackId($id, $this->createdTypeIds);
    }

    /**
     * @Given create type with id :id and value :value
     */
    public function typeWithIdAndValueExists(string $id, string $value): void
    {
        $type = $this->typeFactory->create(
            $value,
            $this->ulidFactory->create($id)
        );
        $this->typeRepository->save($type);
        $this->trackId($id, $this->createdTypeIds);
    }

    /**
     * @Given ensure type exists with id :id
     */
    public function ensureTypeExistsWithId(string $id): void
    {
        $ulid = $this->ulidFactory->create($id);
        $existingType = $this->typeRepository->find($ulid);

        if ($existingType === null) {
            $this->typeWithIdExists($id);
        }
    }

    /**
     * @Given create status with id :id
     */
    public function statusWithIdExists(string $id): void
    {
        $status = $this->getStatus($id);
        $this->statusRepository->save($status);
        $this->trackId($id, $this->createdStatusIds);
    }

    /**
     * @Given create status with id :id and value :value
     */
    public function statusWithIdAndValueExists(string $id, string $value): void
    {
        $status = $this->statusFactory->create(
            $value,
            $this->ulidFactory->create($id)
        );
        $this->statusRepository->save($status);
        $this->trackId($id, $this->createdStatusIds);
    }

    /**
     * @Given ensure status exists with id :id
     */
    public function ensureStatusExistsWithId(string $id): void
    {
        $ulid = $this->ulidFactory->create($id);
        $existingStatus = $this->statusRepository->find($ulid);

        if ($existingStatus === null) {
            $this->statusWithIdExists($id);
        }
    }

    /**
     * @Given create customer with initials :initials
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
     * @Given create customer with email :email
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
     * @Given create customer with phone :phone
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
     * @Given create customer with leadSource :leadSource
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
     * @Given create customer with type value :type and status value :status and id :id
     */
    public function customerWithTypeStatusAndIdExists(
        string $type,
        string $status,
        string $id
    ): void {
        $this->createAndSaveCustomerWithValues(
            $id,
            $this->faker->lexify('??'),
            $this->faker->email(),
            $this->faker->e164PhoneNumber(),
            $this->faker->word(),
            true,
            $type,
            $status
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
     * @Given create customer with confirmed :confirmed
     */
    public function customerWithConfirmedExists(string $confirmed): void
    {
        $id = (string) $this->faker->ulid();
        $this->createAndSaveCustomerWithConfirmationStatus(
            $id,
            $this->faker->lexify('??'),
            $this->faker->email(),
            $this->faker->e164PhoneNumber(),
            $this->faker->word(),
            filter_var($confirmed, FILTER_VALIDATE_BOOLEAN)
        );
    }

    /**
     * @Given create :count customers
     */
    public function createMultipleCustomers(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $id = (string) $this->faker->ulid();
            $this->createAndSaveCustomerWithConfirmationStatus(
                $id,
                $this->faker->lexify('??'),
                $this->faker->email(),
                $this->faker->e164PhoneNumber(),
                $this->faker->word(),
                true
            );
        }
    }

    /**
     * @Given create customer status with value :value
     */
    public function customerStatusWithValueExists(string $value): void
    {
        $id = (string) $this->faker->ulid();
        $status = $this->statusRepository->find($id)
            ?? $this->statusFactory->create(
                $value,
                $this->ulidFactory->create($id)
            );
        $status->setValue($value);
        $this->statusRepository->save($status);
        $this->trackId($id, $this->createdStatusIds);
    }

    /**
     * @Given create customer type with value :value
     */
    public function customerTypeWithValueExists(string $value): void
    {
        $id = (string) $this->faker->ulid();
        $type = $this->getCustomerType($id);
        $type->setValue($value);
        $this->typeRepository->save($type);
        $this->trackId($id, $this->createdTypeIds);
    }

    public function getCustomerType(string $id): CustomerType
    {
        $type = $this->typeFactory->create(
            $this->faker->word(),
            $this->ulidFactory->create($id)
        );
        $this->trackId($id, $this->createdTypeIds);
        return $type;
    }

    public function getStatus(string $id): CustomerStatus
    {
        $status = $this->statusFactory->create(
            $this->faker->word(),
            $this->ulidFactory->create($id)
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
        $this->cleanupCustomers();
        $this->cleanupStatuses();
        $this->cleanupTypes();
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
     * @Then delete status with id :id
     */
    public function deleteStatusById(string $id): void
    {
        $ulid = $this->ulidFactory->create($id);
        $status = $this->statusRepository->find($ulid);
        if ($status !== null) {
            $this->statusRepository->delete($status);
        }
    }

    /**
     * @Then delete type with value :value
     */
    public function deleteTypeByValue(string $value): void
    {
        $this->typeRepository->deleteByValue($value);
    }

    /**
     * @Then delete type with id :id
     */
    public function deleteTypeById(string $id): void
    {
        $ulid = $this->ulidFactory->create($id);
        $type = $this->typeRepository->find($ulid);
        if ($type !== null) {
            $this->typeRepository->delete($type);
        }
    }

    private function cleanupCustomers(): void
    {
        foreach ($this->createdCustomerIds as $id) {
            $this->deleteCustomerById($id);
        }
        $this->createdCustomerIds = [];
    }

    private function cleanupStatuses(): void
    {
        foreach ($this->createdStatusIds as $id) {
            $status = $this->statusRepository->find($id);
            if ($status !== null) {
                $this->statusRepository->delete($status);
            }
        }
        $this->createdStatusIds = [];
    }

    private function cleanupTypes(): void
    {
        foreach ($this->createdTypeIds as $id) {
            $type = $this->typeRepository->find($id);
            if ($type !== null) {
                $this->typeRepository->delete($type);
            }
        }
        $this->createdTypeIds = [];
    }

    /**
     * @param array<string> $storage  The array to store IDs.
     */
    private function trackId(string $id, array &$storage): void
    {
        if (! in_array($id, $storage, true)) {
            $storage[] = $id;
        }
    }

    /**
     * @return array{0: CustomerType, 1: CustomerStatus}
     */
    private function prepareCustomerEntitiesDefault(string $id): array
    {
        $type = $this->getCustomerType($id);
        $status = $this->getStatus($id);

        $type->setValue($this->faker->word());
        $status->setValue($this->faker->word());

        $this->typeRepository->save($type);
        $this->statusRepository->save($status);

        $this->trackId($id, $this->createdTypeIds);
        $this->trackId($id, $this->createdStatusIds);

        return [$type, $status];
    }

    private function mapDynamicDates(string $url): string
    {
        $pattern = '/!%date\((.*?)\),date_interval\((.*?)\)!%/';

        if (! preg_match_all($pattern, $url, $matches, PREG_SET_ORDER)) {
            return $url;
        }

        foreach ($matches as $match) {
            $url = $this->replaceDatePlaceholder($url, $match);
        }

        return $url;
    }

    /**
     * @param array<string> $match
     */
    private function replaceDatePlaceholder(string $url, array $match): string
    {
        $dateTimeFormat = $match[1];
        $dateTimeInterval = $match[2];
        $date = $this->calculateDate($dateTimeInterval);
        $formattedDate = $date->format($dateTimeFormat);

        return str_replace($match[0], $formattedDate, $url);
    }

    private function calculateDate(string $interval): DateTime
    {
        $date = new DateTime();
        $isNegative = str_starts_with($interval, '-');
        $intervalString = $this->extractIntervalString($interval, $isNegative);
        $dateInterval = new DateInterval($intervalString);

        return $this->applyDateInterval($date, $dateInterval, $isNegative);
    }

    private function extractIntervalString(string $interval, bool $isNegative): string
    {
        return $isNegative ? substr($interval, 1) : $interval;
    }

    private function applyDateInterval(
        DateTime $date,
        DateInterval $dateInterval,
        bool $isNegative
    ): DateTime {
        return $isNegative ? $date->sub($dateInterval) : $date->add($dateInterval);
    }

    /**
     * @return array{0: CustomerType, 1: CustomerStatus}
     */
    private function prepareCustomerWithValues(
        string $id,
        string $typeValue,
        string $statusValue
    ): array {
        $type = $this->getCustomerType($id);
        $status = $this->getStatus($id);

        $type->setValue($typeValue);
        $status->setValue($statusValue);

        $this->typeRepository->save($type);
        $this->statusRepository->save($status);

        $this->trackId($id, $this->createdTypeIds);
        $this->trackId($id, $this->createdStatusIds);

        return [$type, $status];
    }

    private function createAndSaveCustomerDefault(
        string $id,
        string $initials,
        string $email,
        string $phone,
        string $leadSource
    ): void {
        $this->createAndSaveCustomerWithConfirmationStatus(
            $id,
            $initials,
            $email,
            $phone,
            $leadSource,
            true
        );
    }

    private function createAndSaveCustomerWithConfirmationStatus(
        string $id,
        string $initials,
        string $email,
        string $phone,
        string $leadSource,
        bool $confirmed
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
            $this->ulidFactory->create($id)
        );
        $this->customerRepository->save($customer);
        $this->trackId($id, $this->createdCustomerIds);
    }

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
            $this->ulidFactory->create($id)
        );
        $this->customerRepository->save($customer);
        $this->trackId($id, $this->createdCustomerIds);
    }

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
            $this->ulidFactory->create($id)
        );
        $customer->setLeadSource($leadSource);
        $this->customerRepository->save($customer);
        $this->trackId($id, $this->createdCustomerIds);
    }
}
