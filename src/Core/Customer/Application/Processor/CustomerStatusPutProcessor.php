<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Core\Customer\Application\DTO\StatusPut;
use App\Core\Customer\Application\Factory\UpdateStatusCommandFactoryInterface;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Exception\CustomerStatusNotFoundException;
use App\Core\Customer\Domain\Repository\StatusRepositoryInterface;
use App\Core\Customer\Domain\ValueObject\CustomerStatusUpdate;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Factory\UlidFactory;

/**
 * @implements ProcessorInterface<StatusPut, CustomerStatus>
 */
final readonly class CustomerStatusPutProcessor implements ProcessorInterface
{
    public function __construct(
        private StatusRepositoryInterface $repository,
        private CommandBusInterface $commandBus,
        private UpdateStatusCommandFactoryInterface $commandFactory,
        private UlidFactory $ulidFactory,
    ) {
    }

    /**
     * @param StatusPut $data
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

    private function dispatchCommand(
        CustomerStatus $customerStatus,
        string $value
    ): void {
        $this->commandBus->dispatch(
            $this->commandFactory->create(
                $customerStatus,
                new CustomerStatusUpdate($value)
            )
        );
    }
}
