<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Core\Customer\Application\DTO\StatusPatch;
use App\Core\Customer\Application\Factory\UpdateStatusCommandFactoryInterface;
use App\Core\Customer\Application\Resolver\CustomerStatusResolver;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\ValueObject\CustomerStatusUpdate;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use InvalidArgumentException;

/**
 * @implements ProcessorInterface<StatusPatch, CustomerStatus>
 */
final readonly class CustomerStatusPatchProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private UpdateStatusCommandFactoryInterface $commandFactory,
        private CustomerStatusResolver $customerStatusResolver,
    ) {
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint
     *
     * @param array<string,string> $uriVariables
     * @param array<string, CustomerStatus|array|string|int|float|bool|null> $context
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): CustomerStatus {
        if (!$data instanceof StatusPatch) {
            throw new InvalidArgumentException(sprintf(
                'Expected %s, got %s',
                StatusPatch::class,
                get_debug_type($data)
            ));
        }

        $customerStatus = $this->customerStatusResolver->resolve(
            $data,
            $context,
            $operation
        );

        // Only update if value is explicitly provided and not empty
        if ($data->value !== null && trim($data->value) !== '') {
            $this->dispatchCommand($customerStatus, $data->value);
        }

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
