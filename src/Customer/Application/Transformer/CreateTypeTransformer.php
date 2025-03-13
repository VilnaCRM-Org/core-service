<?php

declare(strict_types=1);

namespace App\Customer\Application\Transformer;

use App\Customer\Application\Command\CreateTypeCommand;
use App\Customer\Domain\Entity\CustomerType;
use App\Customer\Domain\Factory\TypeFactoryInterface;
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use Symfony\Component\Uid\Factory\UlidFactory;

final class CreateTypeTransformer
{
    public function __construct(
        private TypeFactoryInterface $typeFactory,
        private UlidTransformer $transformer,
        private UlidFactory $ulidFactory,
    ) {
    }

    public function transform(
        CreateTypeCommand $command
    ): CustomerType {
        return $this->typeFactory->create(
            $command->value,
            $this->transformer->transformFromSymfonyUlid(
                $this->ulidFactory->create()
            )
        );
    }
}
