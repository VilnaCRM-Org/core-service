<?php

declare(strict_types=1);

namespace App\Shared\Application\Command;

use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Repository\StatusRepositoryInterface;
use App\Shared\Application\Fixture\SchemathesisFixtures;
use App\Shared\Infrastructure\Factory\UlidFactory;

final readonly class SchemathesisCustomerStatusSeeder
{
    private const STATUS_DEFINITIONS = [
        'default' => [
            'id' => SchemathesisFixtures::CUSTOMER_STATUS_ID,
            'value' => SchemathesisFixtures::CUSTOMER_STATUS_NAME,
        ],
        'update' => [
            'id' => SchemathesisFixtures::UPDATE_CUSTOMER_STATUS_ID,
            'value' => SchemathesisFixtures::UPDATE_CUSTOMER_STATUS_NAME,
        ],
        'delete' => [
            'id' => SchemathesisFixtures::DELETE_CUSTOMER_STATUS_ID,
            'value' => SchemathesisFixtures::DELETE_CUSTOMER_STATUS_NAME,
        ],
    ];

    public function __construct(
        private StatusRepositoryInterface $statusRepository,
        private UlidFactory $ulidFactory
    ) {
    }

    /**
     * @return array<string,CustomerStatus>
     */
    public function seedStatuses(): array
    {
        $results = [];

        foreach (self::STATUS_DEFINITIONS as $key => $definition) {
            $results[$key] = $this->seedStatus(
                $definition['id'],
                $definition['value']
            );
        }

        return $results;
    }

    private function seedStatus(string $id, string $value): CustomerStatus
    {
        $status = $this->statusRepository->find($id);

        if ($status === null) {
            return $this->createStatus($id, $value);
        }

        $this->updateStatus($status, $value);

        return $status;
    }

    private function createStatus(string $id, string $value): CustomerStatus
    {
        $ulid = $this->ulidFactory->create($id);

        $status = new CustomerStatus($value, $ulid);
        $this->statusRepository->save($status);

        return $status;
    }

    private function updateStatus(CustomerStatus $status, string $value): void
    {
        $status->setValue($value);
        $this->statusRepository->save($status);
    }
}
