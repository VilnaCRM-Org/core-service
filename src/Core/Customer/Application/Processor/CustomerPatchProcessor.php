<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Core\Customer\Application\DTO\CustomerPatch;
use App\Core\Customer\Application\Factory\UpdateCustomerCommandFactoryInterface;
use App\Core\Customer\Application\Resolver\CustomerPatchUpdateResolver;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Exception\CustomerNotFoundException;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Core\Customer\Domain\ValueObject\CustomerUpdate;
use App\Shared\Application\Extractor\PatchUlidExtractor;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Factory\UlidFactory;

/**
 * @implements ProcessorInterface<CustomerPatch, Customer>
 */
final readonly class CustomerPatchProcessor implements ProcessorInterface
{
    public function __construct(
        private CustomerRepositoryInterface $repository,
        private CommandBusInterface $commandBus,
        private UpdateCustomerCommandFactoryInterface $commandFactory,
        private CustomerPatchUpdateResolver $patchUpdateResolver,
        private PatchUlidExtractor $patchUlidExtractor,
        private UlidFactory $ulidTransformer,
    ) {
    }

    /**
     * @param CustomerPatch       $data
     * @param array<string,string>   $context
     * @param array<string,string>   $uriVariables
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): Customer {
        $ulid = $this->patchUlidExtractor->extract(
            $uriVariables,
            $data->id,
            static fn () => new CustomerNotFoundException()
        );

        $customer = $this->retrieveCustomer($ulid);
        $customerUpdate = $this->patchUpdateResolver->build($data, $customer);
        $this->dispatchUpdateCommand($customer, $customerUpdate);

        return $customer;
    }

    private function retrieveCustomer(string $ulid): Customer
    {
        $ulidObject = $this->ulidTransformer->create($ulid);

        return $this->repository->find($ulidObject)
            ?? throw new CustomerNotFoundException();
    }

    private function dispatchUpdateCommand(
        Customer $customer,
        CustomerUpdate $update
    ): void {
        $this->commandBus->dispatch(
            $this->commandFactory->create($customer, $update)
        );
    }
}
