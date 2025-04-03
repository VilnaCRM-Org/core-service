<?php

namespace App\Tests\Behat\CustomerContext;

use App\Customer\Domain\Entity\CustomerStatus;
use App\Customer\Domain\Entity\CustomerType;
use App\Customer\Domain\Factory\CustomerFactoryInterface;
use App\Customer\Domain\Factory\StatusFactoryInterface;
use App\Customer\Domain\Factory\TypeFactoryInterface;
use App\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Customer\Domain\Repository\StatusRepositoryInterface;
use App\Customer\Domain\Repository\TypeRepositoryInterface;
use App\Shared\Domain\Factory\UlidFactoryInterface;
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use App\Tests\Unit\UlidProvider;
use Behat\Behat\Context\Context;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\Uid\Ulid;

class CustomerContext implements Context
{
    private Generator $faker;

    public function __construct(
        private TypeRepositoryInterface $typeRepository,
        private StatusRepositoryInterface $statusRepository,
        private UlidFactoryInterface $ulidFactory,
        private UlidTransformer $ulidTransformer,
        private CustomerRepositoryInterface $customerRepository,
        private CustomerFactoryInterface $customerFactory,
        private StatusFactoryInterface $statusFactory,
        private TypeFactoryInterface $typeFactory,
    ) {
        $this->faker = Factory::create();
        $this->faker->addProvider(new UlidProvider($this->faker));
    }

    /**
     * @Given customer with id :id exists
     */
    public function customerWithIdExists(string $id): void
    {

        $type = $this->getCustomerType((string)$this->faker->ulid());
        $status = $this->getStatus((string)$this->faker->ulid());
        $this->typeRepository->save($type);
        $this->statusRepository->save($status);

        $user = $this->customerRepository->find($id) ??
            $this->customerFactory->create(
                $this->faker->name(),
                $this->faker->email(),
                $this->faker->phoneNumber(),
                $this->faker->name(),
                $type,
                $status,
                true,
                $this->ulidTransformer->transformFromSymfonyUlid(new Ulid($id))
            );
        $this->customerRepository->save($user);
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
}
