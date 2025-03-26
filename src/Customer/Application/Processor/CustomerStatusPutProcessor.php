<?php

declare(strict_types=1);

namespace App\Customer\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Customer\Application\DTO\CustomerStatusPutDto;
use App\Customer\Application\Factory\UpdateCustomerStatusCommandFactoryInterface;
use App\Customer\Domain\Entity\CustomerStatus;
use App\Customer\Domain\Exception\CustomerStatusNotFoundException;
use App\Customer\Domain\Repository\StatusRepositoryInterface;
use App\Customer\Domain\ValueObject\CustomerStatusUpdate;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Factory\UlidFactory;

/**
 * @implements ProcessorInterface<CustomerStatusPutDto, CustomerStatus>
 */
final readonly class CustomerStatusPutProcessor implements ProcessorInterface
{
    public function __construct(
        private StatusRepositoryInterface $repository,
        private CommandBusInterface $commandBus,
        private UpdateCustomerStatusCommandFactoryInterface $commandFactory,
        private UlidFactory $ulidFactory,
    ) {
    }

    /**
     * @param CustomerStatusPutDto $data
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

        $this->dispatchCommand($customerStatus, $data->value);

        return $customerStatus;
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
