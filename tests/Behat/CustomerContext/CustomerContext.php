<?php

declare(strict_types=1);

namespace App\Tests\Behat\CustomerContext;

use App\Core\Customer\Domain\Factory\CustomerFactoryInterface;
use App\Core\Customer\Domain\Factory\StatusFactoryInterface;
use App\Core\Customer\Domain\Factory\TypeFactoryInterface;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Core\Customer\Domain\Repository\StatusRepositoryInterface;
use App\Core\Customer\Domain\Repository\TypeRepositoryInterface;
use App\Shared\Infrastructure\Factory\UlidFactory;
use App\Tests\Behat\CustomerContext\Builder\CustomerTestDataBuilder;
use App\Tests\Behat\CustomerContext\Cleaner\TestDataCleaner;
use App\Tests\Behat\CustomerContext\Factory\TestEntityFactory;
use App\Tests\Behat\CustomerContext\Manager\EntityManager;
use App\Tests\Behat\CustomerContext\Mapper\DateUrlMapper;
use App\Tests\Unit\UlidProvider;
use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Doctrine\ODM\MongoDB\DocumentManager;
use Faker\Factory;
use Faker\Generator;
use TwentytwoLabs\BehatOpenApiExtension\Context\RestContext;

final class CustomerContext implements Context, SnippetAcceptingContext
{
    private Generator $faker;
    private CustomerTestDataBuilder $customerBuilder;
    private TestEntityFactory $entityFactory;
    private TestDataCleaner $dataCleaner;
    private EntityManager $entityManager;
    private DateUrlMapper $dateUrlMapper;

    /** @psalm-suppress UndefinedClass */
    private RestContext $restContext;

    public function __construct(
        TypeRepositoryInterface $typeRepository,
        StatusRepositoryInterface $statusRepository,
        UlidFactory $ulidFactory,
        CustomerRepositoryInterface $customerRepository,
        DocumentManager $documentManager,
        CustomerFactoryInterface $customerFactory,
        StatusFactoryInterface $statusFactory,
        TypeFactoryInterface $typeFactory,
    ) {
        $this->faker = Factory::create();
        $this->faker->addProvider(new UlidProvider($this->faker));

        $this->initializeHelpers(
            $typeRepository,
            $statusRepository,
            $ulidFactory,
            $customerRepository,
            $documentManager,
            $customerFactory,
            $statusFactory,
            $typeFactory
        );
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
     * @AfterScenario
     */
    public function cleanupCreatedCustomersAndEntities(AfterScenarioScope $scope): void
    {
        $this->dataCleaner->cleanupAll();
    }

    /**
     * @When I send a GET data request to :url
     *
     * @psalm-suppress UndefinedClass
     */
    public function iSendAGetDataRequestTo(string $url): void
    {
        $mappedUrl = $this->dateUrlMapper->map($url);
        $mappedUrl = getenv('BASE_URL') . $mappedUrl;
        $this->restContext->iSendARequestTo('GET', $mappedUrl);
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
        $type = $this->entityFactory->createType($id);
        $this->entityManager->saveType($type);
        $this->dataCleaner->trackType($id);
    }

    /**
     * @Given create type with id :id and value :value
     */
    public function typeWithIdAndValueExists(string $id, string $value): void
    {
        $type = $this->entityFactory->createType($id, $value);
        $this->entityManager->saveType($type);
        $this->dataCleaner->trackType($id);
    }

    /**
     * @Given ensure type exists with id :id
     */
    public function ensureTypeExistsWithId(string $id): void
    {
        $ulid = $this->entityFactory->createUlid($id);
        $existingType = $this->entityManager->findType($ulid);

        if ($existingType === null) {
            $this->typeWithIdExists($id);
        }
    }

    /**
     * @Given create status with id :id
     */
    public function statusWithIdExists(string $id): void
    {
        $status = $this->entityFactory->createStatus($id);
        $this->entityManager->saveStatus($status);
        $this->dataCleaner->trackStatus($id);
    }

    /**
     * @Given create status with id :id and value :value
     */
    public function statusWithIdAndValueExists(string $id, string $value): void
    {
        $status = $this->entityFactory->createStatus($id, $value);
        $this->entityManager->saveStatus($status);
        $this->dataCleaner->trackStatus($id);
    }

    /**
     * @Given ensure status exists with id :id
     */
    public function ensureStatusExistsWithId(string $id): void
    {
        $ulid = $this->entityFactory->createUlid($id);
        $existingStatus = $this->entityManager->findStatus($ulid);

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
        $this->createCustomerWithBuilder(
            $id,
            static fn ($builder) => $builder->withInitials($initials)
        );
    }

    /**
     * @Given create customer with email :email
     */
    public function customerWithEmailExists(string $email): void
    {
        $id = (string) $this->faker->ulid();
        $this->createCustomerWithBuilder(
            $id,
            static fn ($builder) => $builder->withEmail($email)->withLeadSource('defaultSource')
        );
    }

    /**
     * @Given create customer with id :id and email :email
     */
    public function customerWithIdAndEmailExists(string $id, string $email): void
    {
        $this->createCustomerWithBuilder(
            $id,
            static fn ($builder) => $builder->withEmail($email)->withLeadSource('defaultSource')
        );
    }

    /**
     * @Given create customer with phone :phone
     */
    public function customerWithPhoneExists(string $phone): void
    {
        $id = (string) $this->faker->ulid();
        $this->createCustomerWithBuilder($id, static fn ($builder) => $builder->withPhone($phone));
    }

    /**
     * @Given create customer with leadSource :leadSource
     */
    public function customerWithLeadSourceExists(string $leadSource): void
    {
        $id = (string) $this->faker->ulid();
        $this->createCustomerWithBuilder(
            $id,
            static fn ($builder) => $builder->withLeadSource($leadSource)
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
        $this->createCustomerWithTypeAndStatus($id, $type, $status);
    }

    /**
     * @Given customer with type value :typeValue and status value :statusValue exists
     */
    public function customerWithTypeAndStatusExists(string $typeValue, string $statusValue): void
    {
        $id = (string) $this->faker->ulid();
        $this->createCustomerWithTypeAndStatus($id, $typeValue, $statusValue);
    }

    /**
     * @Given create customer with confirmed :confirmed
     */
    public function customerWithConfirmedExists(string $confirmed): void
    {
        $id = (string) $this->faker->ulid();
        $isConfirmed = filter_var($confirmed, FILTER_VALIDATE_BOOLEAN);
        $this->createCustomerWithBuilder(
            $id,
            static fn ($builder) => $builder->withConfirmed($isConfirmed)
        );
    }

    /**
     * @Given create :count customers
     */
    public function createMultipleCustomers(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $id = (string) $this->faker->ulid();
            $this->createCustomerWithDefaults($id);
        }
    }

    /**
     * @Given create customer status with value :value
     */
    public function customerStatusWithValueExists(string $value): void
    {
        $id = (string) $this->faker->ulid();
        $status = $this->entityFactory->createStatus($id, $value);
        $this->entityManager->saveStatus($status);
        $this->dataCleaner->trackStatus($id);
    }

    /**
     * @Given create customer type with value :value
     */
    public function customerTypeWithValueExists(string $value): void
    {
        $id = (string) $this->faker->ulid();
        $type = $this->entityFactory->createType($id, $value);
        $this->entityManager->saveType($type);
        $this->dataCleaner->trackType($id);
    }

    /**
     * @Then delete customer with id :id
     */
    public function deleteCustomerById(mixed $id): void
    {
        $customer = $this->entityManager->findCustomer($id);
        if ($customer !== null) {
            $this->entityManager->deleteCustomer($customer);
        }
    }

    /**
     * @Then delete customer with email :email
     */
    public function deleteCustomerByEmail(string $email): void
    {
        $this->entityManager->deleteCustomerByEmail($email);
    }

    /**
     * @Then delete status with value :value
     */
    public function deleteStatusByValue(string $value): void
    {
        $this->entityManager->deleteStatusByValue($value);
    }

    /**
     * @Then delete status with id :id
     */
    public function deleteStatusById(string $id): void
    {
        $ulid = $this->entityFactory->createUlid($id);
        $status = $this->entityManager->findStatus($ulid);
        if ($status !== null) {
            $this->entityManager->deleteStatus($status);
        }
    }

    /**
     * @Then delete type with value :value
     */
    public function deleteTypeByValue(string $value): void
    {
        $this->entityManager->deleteTypeByValue($value);
    }

    /**
     * @Then delete type with id :id
     */
    public function deleteTypeById(string $id): void
    {
        $ulid = $this->entityFactory->createUlid($id);
        $type = $this->entityManager->findType($ulid);
        if ($type !== null) {
            $this->entityManager->deleteType($type);
        }
    }

    private function initializeHelpers(
        TypeRepositoryInterface $typeRepository,
        StatusRepositoryInterface $statusRepository,
        UlidFactory $ulidFactory,
        CustomerRepositoryInterface $customerRepository,
        DocumentManager $documentManager,
        CustomerFactoryInterface $customerFactory,
        StatusFactoryInterface $statusFactory,
        TypeFactoryInterface $typeFactory
    ): void {
        $this->entityManager = new EntityManager(
            $customerRepository,
            $documentManager,
            $statusRepository,
            $typeRepository
        );
        $this->entityFactory = new TestEntityFactory(
            $typeFactory,
            $statusFactory,
            $ulidFactory,
            $this->faker
        );
        $this->customerBuilder = new CustomerTestDataBuilder($customerFactory, $this->faker);
        $this->dataCleaner = new TestDataCleaner(
            $customerRepository,
            $statusRepository,
            $typeRepository,
            $ulidFactory
        );
        $this->dateUrlMapper = new DateUrlMapper();
    }

    private function createCustomerWithDefaults(string $id): void
    {
        [$type, $status] = $this->entityFactory->createTypeAndStatus($id);
        $this->entityManager->saveType($type);
        $this->entityManager->saveStatus($status);
        $this->dataCleaner->trackType($id);
        $this->dataCleaner->trackStatus($id);

        $customer = $this->customerBuilder
            ->reset()
            ->withUlid($this->entityFactory->createUlid($id))
            ->withType($type)
            ->withStatus($status)
            ->build();

        $this->entityManager->saveCustomer($customer);
        $this->dataCleaner->trackCustomer($id);
    }

    private function createCustomerWithBuilder(string $id, callable $configurator): void
    {
        [$type, $status] = $this->entityFactory->createTypeAndStatus($id);
        $this->entityManager->saveType($type);
        $this->entityManager->saveStatus($status);
        $this->dataCleaner->trackType($id);
        $this->dataCleaner->trackStatus($id);

        $builder = $this->customerBuilder
            ->reset()
            ->withUlid($this->entityFactory->createUlid($id))
            ->withType($type)
            ->withStatus($status);

        $customer = $configurator($builder)->build();

        $this->entityManager->saveCustomer($customer);
        $this->dataCleaner->trackCustomer($id);
    }

    private function createCustomerWithTypeAndStatus(
        string $id,
        string $typeValue,
        string $statusValue
    ): void {
        [$type, $status] = $this->entityFactory->createTypeAndStatusWithValues(
            $id,
            $typeValue,
            $statusValue
        );
        $this->entityManager->saveType($type);
        $this->entityManager->saveStatus($status);
        $this->dataCleaner->trackType($id);
        $this->dataCleaner->trackStatus($id);

        $customer = $this->customerBuilder
            ->reset()
            ->withUlid($this->entityFactory->createUlid($id))
            ->withType($type)
            ->withStatus($status)
            ->build();

        $this->entityManager->saveCustomer($customer);
        $this->dataCleaner->trackCustomer($id);
    }
}
