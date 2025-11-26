<?php

declare(strict_types=1);

namespace App\Tests\Behat\CustomerContext\Cleaner;

use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Core\Customer\Domain\Repository\StatusRepositoryInterface;
use App\Core\Customer\Domain\Repository\TypeRepositoryInterface;
use App\Shared\Infrastructure\Factory\UlidFactory;

final class TestDataCleaner
{
    /** @var array<string> */
    private array $customerIds = [];

    /** @var array<string> */
    private array $statusIds = [];

    /** @var array<string> */
    private array $typeIds = [];

    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly StatusRepositoryInterface $statusRepository,
        private readonly TypeRepositoryInterface $typeRepository,
        private readonly UlidFactory $ulidFactory
    ) {
    }

    public function trackCustomer(string $id): void
    {
        $this->track($id, $this->customerIds);
    }

    public function trackStatus(string $id): void
    {
        $this->track($id, $this->statusIds);
    }

    public function trackType(string $id): void
    {
        $this->track($id, $this->typeIds);
    }

    public function cleanupAll(): void
    {
        $this->cleanupCustomers();
        $this->cleanupStatuses();
        $this->cleanupTypes();
    }

    public function cleanupCustomers(): void
    {
        foreach ($this->customerIds as $id) {
            $ulid = $this->ulidFactory->create($id);
            $customer = $this->customerRepository->find($ulid);
            if ($customer !== null) {
                $this->customerRepository->delete($customer);
            }
        }
        $this->customerIds = [];
    }

    public function cleanupStatuses(): void
    {
        foreach ($this->statusIds as $id) {
            $ulid = $this->ulidFactory->create($id);
            $status = $this->statusRepository->find($ulid);
            if ($status !== null) {
                $this->statusRepository->delete($status);
            }
        }
        $this->statusIds = [];
    }

    public function cleanupTypes(): void
    {
        foreach ($this->typeIds as $id) {
            $ulid = $this->ulidFactory->create($id);
            $type = $this->typeRepository->find($ulid);
            if ($type !== null) {
                $this->typeRepository->delete($type);
            }
        }
        $this->typeIds = [];
    }

    /**
     * @param array<string> $storage
     */
    private function track(string $id, array &$storage): void
    {
        if (! in_array($id, $storage, true)) {
            $storage[] = $id;
        }
    }
}
