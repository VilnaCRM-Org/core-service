<?php

declare(strict_types=1);

namespace App\Customer\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Customer\Application\Command\CreateTypeCommandFactoryInterface;
use App\Customer\Application\DTO\CustomerCreateDto;
use App\Customer\Domain\Entity\Customer;
use App\Shared\Domain\Bus\Command\CommandBusInterface;

/**
 * @implements ProcessorInterface<CustomerCreateDto, Customer>
 */
final readonly class CreateCustomerTypeProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private CreateTypeCommandFactoryInterface $createTypeCommandFactory
    ) {
    }

    /**
     * @param CustomerCreateDto $data
     * @param array<string,string> $context
     * @param array<string,string> $uriVariables
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ) {
        $command = $this->createTypeCommandFactory->create(
            $data->value
        );
        $this->commandBus->dispatch($command);

        return $command->getResponse()->customerType;
    }
}
