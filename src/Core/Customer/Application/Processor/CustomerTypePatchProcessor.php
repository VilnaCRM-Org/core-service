<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Core\Customer\Application\DTO\TypePatch;
use App\Core\Customer\Application\Factory\UpdateTypeCommandFactoryInterface;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Exception\CustomerTypeNotFoundException;
use App\Core\Customer\Domain\Repository\TypeRepositoryInterface;
use App\Core\Customer\Domain\ValueObject\CustomerTypeUpdate;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Factory\UlidFactory;

/**
 * @implements ProcessorInterface<TypePatch, CustomerType>
 */
final readonly class CustomerTypePatchProcessor implements ProcessorInterface
{
    public function __construct(
        private TypeRepositoryInterface $repository,
        private CommandBusInterface $commandBus,
        private UpdateTypeCommandFactoryInterface $commandFactory,
        private UlidFactory $ulidFactory,
    ) {
    }

    /**
     * @param TypePatch $data
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
        $iri = sprintf('/api/customer_types/%s', $ulid);
        $customerType = $this->repository->find(
            $this->ulidFactory->create($ulid)
        ) ?? throw CustomerTypeNotFoundException::withIri($iri);

        $newValue = $this->getNewValue($data->value, $customerType->getValue());

        $this->dispatchCommand($customerType, $newValue);

        return $customerType;
    }

    private function getNewValue(
        ?string $newValue,
        string $defaultValue
    ): string {
        return $this->hasValidContent($newValue) ? $newValue : $defaultValue;
    }

    private function hasValidContent(?string $value): bool
    {
        if ($value === null) {
            return false;
        }

        return strlen(trim($value)) > 0;
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
