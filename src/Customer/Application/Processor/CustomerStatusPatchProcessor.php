<?php

declare(strict_types=1);

namespace App\Customer\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Customer\Application\DTO\CustomerStatusPatchDto;
use App\Customer\Application\Factory\UpdateCustomerStatusCommandFactoryInterface;
use App\Customer\Domain\Entity\CustomerStatus;
use App\Customer\Domain\Exception\CustomerStatusNotFoundException;
use App\Customer\Domain\Repository\StatusRepositoryInterface;
use App\Customer\Domain\ValueObject\CustomerStatusUpdate;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Factory\UlidFactory;

/**
 * @implements ProcessorInterface<CustomerStatusPatchDto, CustomerStatus>
 */
final readonly class CustomerStatusPatchProcessor implements ProcessorInterface
{
    public function __construct(
        private StatusRepositoryInterface $repository,
        private CommandBusInterface $commandBus,
        private UpdateCustomerStatusCommandFactoryInterface $commandFactory,
        private UlidFactory $ulidFactory,
    ) {
    }

    /**
     * @param CustomerStatusPatchDto $data
     * @param array<string,string> $context
     * @param array<string,string> $uriVariables
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): CustomerStatus {
        $ulid = $uriVariables['ulid'];
        $customerStatus = $this->repository->find(
            $this->ulidFactory->create($ulid)
        ) ?? throw new CustomerStatusNotFoundException();

        $newValue = $this->getNewValue($data->value, $customerStatus->getValue());

        $this->dispatchCommand($customerStatus, $newValue);

        return $customerStatus;
    }

    private function getNewValue(?string $newValue, string $defaultValue): string
    {
        return strlen(trim($newValue ?? '')) > 0 ? $newValue : $defaultValue;
    }

    private function dispatchCommand(CustomerStatus $customerStatus, string $value): void
    {
        $this->commandBus->dispatch(
            $this->commandFactory->create(
                $customerStatus,
                new CustomerStatusUpdate($value)
            )
        );
    }
}
