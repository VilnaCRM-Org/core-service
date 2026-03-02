<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Core\Customer\Application\Command\DeleteCustomerCommand;
use App\Core\Customer\Domain\Entity\Customer;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use InvalidArgumentException;

/**
 * @implements ProcessorInterface<Customer, null>
 */
final readonly class CustomerDeleteProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @param array<string, string> $context
     * @param array<string, string> $uriVariables
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): null {
        if (!$data instanceof Customer) {
            throw new InvalidArgumentException('Expected instance of Customer');
        }

        $this->commandBus->dispatch(new DeleteCustomerCommand($data));

        return null;
    }
}
