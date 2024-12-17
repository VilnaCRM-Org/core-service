<?php

namespace App\Customer\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Customer\Application\Command\PutCustomerCommand;
use App\Customer\Application\DTO\CustomerCreateDto;
use App\Customer\Application\DTO\CustomerPutDto;
use App\Customer\Application\Factory\CreateCustomerCommandFactoryInterface;
use App\Customer\Application\Factory\PutCustomerCommandFactory;
use App\Customer\Application\Factory\PutCustomerCommandFactoryInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;

class PutCustomerProcessor implements ProcessorInterface
{

    public function __construct(private CommandBusInterface                $commandBus,
                                private PutCustomerCommandFactoryInterface $commandFactory)
    {
    }

    /**
     * @param CustomerPutDto $data
     * @param array<string,string> $context
     * @param array<string,string> $uriVariables
     */
    public function process(mixed     $data,
                            Operation $operation,
                            array     $uriVariables = [],
                            array     $context = [])
    {
        $customerId = $uriVariables['id'];
        $command = $this->commandFactory->create(
            $customerId,
            $data->initials,
            $data->email,
            $data->phone,
            $data->leadSource,
            $data->type,
            $data->status);
        $this->commandBus->dispatch($command);
        return $command->getResponse()->createdCustomer;
    }
}