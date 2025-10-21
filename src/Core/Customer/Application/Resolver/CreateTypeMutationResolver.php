<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Core\Customer\Application\Factory\CreateTypeFactoryInterface;
use App\Core\Customer\Application\Transformer\CreateTypeMutationInputTransformer;
use App\Core\Customer\Application\Transformer\TypeTransformerInterface;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Shared\Application\GraphQL\MutationInputValidator;
use App\Shared\Domain\Bus\Command\CommandBusInterface;

final readonly class CreateTypeMutationResolver implements MutationResolverInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private MutationInputValidator $validator,
        private CreateTypeMutationInputTransformer $transformer,
        private CreateTypeFactoryInterface $createTypeCommandFactory,
        private TypeTransformerInterface $typeTransformer,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function __invoke(?object $item, array $context): CustomerType
    {
        $input = $context['args']['input'];
        $mutationInput = $this->transformer->transform($input);
        $this->validator->validate($mutationInput);

        $customerType = $this->typeTransformer->transform($input['value']);
        $command = $this->createTypeCommandFactory->create($customerType);
        $this->commandBus->dispatch($command);

        return $customerType;
    }
}
