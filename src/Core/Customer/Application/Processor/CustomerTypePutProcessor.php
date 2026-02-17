<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Core\Customer\Application\DTO\TypePut;
use App\Core\Customer\Application\Factory\UpdateTypeCommandFactoryInterface;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Exception\CustomerTypeNotFoundException;
use App\Core\Customer\Domain\Repository\TypeRepositoryInterface;
use App\Core\Customer\Domain\ValueObject\CustomerTypeUpdate;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Factory\UlidFactory;

/**
 * @implements ProcessorInterface<TypePut, CustomerType>
 */
final readonly class CustomerTypePutProcessor implements ProcessorInterface
{
    public function __construct(
        private TypeRepositoryInterface $repository,
        private CommandBusInterface $commandBus,
        private UpdateTypeCommandFactoryInterface $commandFactory,
        private UlidFactory $ulidTransformer,
    ) {
    }

    /**
     * @param TypePut $data
     * @param array<string,string> $context
     * @param array<string,string> $uriVariables
     */
    #[\Override]
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): CustomerType {
        $ulid = $uriVariables['ulid'];
        $iri = sprintf('/api/customer_types/%s', $ulid);
        $customerType = $this->repository->find(
            $this->ulidTransformer->create($ulid)
        ) ?? throw CustomerTypeNotFoundException::withIri($iri);

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
