<?php

declare(strict_types=1);

namespace App\Customer\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Customer\Application\DTO\CustomerTypeCreateDto;
use App\Customer\Application\Factory\CreateTypeFactoryInterface;
use App\Customer\Domain\Entity\Customer;
use App\Customer\Domain\Entity\CustomerType;
use App\Shared\Domain\Bus\Command\CommandBusInterface;

/**
 * @implements ProcessorInterface<CustomerTypeCreateDto, Customer>
 */
final readonly class CreateCustomerTypeProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private CreateTypeFactoryInterface $createTypeCommandFactory
    ) {
    }

    /**
     * @param CustomerTypeCreateDto $data
     * @param array<string,string> $context
     * @param array<string,string> $uriVariables
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): CustomerType {
        $command = $this->createTypeCommandFactory->create(
            $data->value
        );
        $this->commandBus->dispatch($command);

        return $command->getResponse()->customerType;
    }
}
