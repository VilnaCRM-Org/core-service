<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Transformer;

use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Factory\TypeFactoryInterface;
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use Symfony\Component\Uid\Factory\UlidFactory;

final readonly class CreateTypeTransformer implements
    TypeTransformerInterface
{
    public function __construct(
        private TypeFactoryInterface $typeFactory,
        private UlidTransformer $transformer,
        private UlidFactory $ulidFactory,
    ) {
    }

    public function transform(
        string $value
    ): CustomerType {
        return $this->typeFactory->create(
            $value,
            $this->transformer->transformFromSymfonyUlid(
                $this->ulidFactory->create()
            )
        );
    }
}
