<?php

declare(strict_types=1);

namespace App\Customer\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Customer\Application\DTO\CustomerTypePutDto;
use App\Customer\Application\Factory\UpdateCustomerTypeCommandFactoryInterface;
use App\Customer\Domain\Entity\CustomerType;
use App\Customer\Domain\Exception\CustomerTypeNotFoundException;
use App\Customer\Domain\Repository\TypeRepositoryInterface;
use App\Customer\Domain\ValueObject\CustomerTypeUpdate;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Factory\UlidFactory;

/**
 * @implements ProcessorInterface<CustomerTypePutDto, CustomerType>
 */
final readonly class CustomerTypePutProcessor implements ProcessorInterface
{
    public function __construct(
        private TypeRepositoryInterface $repository,
        private CommandBusInterface $commandBus,
        private UpdateCustomerTypeCommandFactoryInterface $commandFactory,
        private UlidFactory $ulidTransformer,
    ) {
    }

    /**
     * @param CustomerTypePutDto $data
     * @param array<string,string> $context
     * @param array<string,string> $uriVariables
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): CustomerType {
        $ulid = $uriVariables['ulid'];
        $customerType = $this->repository->find(
            $this->ulidTransformer->create($ulid)
        ) ?? throw new CustomerTypeNotFoundException();

        $this->dispatchCommand($customerType, $data->value);

        return $customerType;
    }

    private function dispatchCommand(
        CustomerType $customerType,
        string $newValue
    ): void {
        $this->commandBus->dispatch(
            $this->commandFactory->create(
                $customerType,
                new CustomerTypeUpdate($newValue)
            )
        );
    }
}
