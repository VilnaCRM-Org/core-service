<?php

declare(strict_types=1);

namespace App\Shared\Application\Command;

use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Repository\TypeRepositoryInterface;
use App\Shared\Application\Fixture\SchemathesisFixtures;
use App\Shared\Infrastructure\Factory\UlidFactory;

final readonly class SchemathesisCustomerTypeSeeder
{
    private const TYPE_DEFINITIONS = [
        'default' => [
            'id' => SchemathesisFixtures::CUSTOMER_TYPE_ID,
            'value' => SchemathesisFixtures::CUSTOMER_TYPE_NAME,
        ],
        'update' => [
            'id' => SchemathesisFixtures::UPDATE_CUSTOMER_TYPE_ID,
            'value' => SchemathesisFixtures::UPDATE_CUSTOMER_TYPE_NAME,
        ],
        'delete' => [
            'id' => SchemathesisFixtures::DELETE_CUSTOMER_TYPE_ID,
            'value' => SchemathesisFixtures::DELETE_CUSTOMER_TYPE_NAME,
        ],
    ];

    public function __construct(
        private TypeRepositoryInterface $typeRepository,
        private UlidFactory $ulidFactory
    ) {
    }

    /**
     * @return array<string,CustomerType>
     */
    public function seedTypes(): array
    {
        $results = [];

        foreach (self::TYPE_DEFINITIONS as $key => $definition) {
            $results[$key] = $this->seedType(
                $definition['id'],
                $definition['value']
            );
        }

        return $results;
    }

    private function seedType(string $id, string $value): CustomerType
    {
        $type = $this->typeRepository->find($id);

        if ($type === null) {
            return $this->createType($id, $value);
        }

        $this->updateType($type, $value);

        return $type;
    }

    private function createType(string $id, string $value): CustomerType
    {
        $ulid = $this->ulidFactory->create($id);

        $type = new CustomerType($value, $ulid);
        $this->typeRepository->save($type);

        return $type;
    }

    private function updateType(CustomerType $type, string $value): void
    {
        $type->setValue($value);
        $this->typeRepository->save($type);
    }
}
