<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Resolver;

use App\Core\Customer\Application\Command\UpdateCustomerTypeCommand;
use App\Core\Customer\Application\Factory\UpdateTypeCommandFactoryInterface;
use App\Core\Customer\Application\MutationInput\UpdateTypeMutationInput;
use App\Core\Customer\Application\Resolver\UpdateTypeMutationResolver;
use App\Core\Customer\Application\Transformer\UpdateTypeMutationInputTransformer;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Exception\CustomerTypeNotFoundException;
use App\Core\Customer\Domain\Repository\TypeRepositoryInterface;
use App\Core\Customer\Domain\ValueObject\CustomerTypeUpdate;
use App\Shared\Application\GraphQL\MutationInputValidator;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Factory\UlidFactory;
use App\Tests\Unit\UnitTestCase;

final class UpdateTypeMutationResolverTest extends UnitTestCase
{
    public function testInvokeUpdatesType(): void
    {
        $commandBus = $this->createMock(CommandBusInterface::class);
        $validator = $this->createMock(MutationInputValidator::class);
        $transformer = $this->createMock(UpdateTypeMutationInputTransformer::class);
        $factory = $this->createMock(UpdateTypeCommandFactoryInterface::class);
        $repository = $this->createMock(TypeRepositoryInterface::class);
        $ulidFactory = new UlidFactory();

        $resolver = new UpdateTypeMutationResolver(
            $commandBus,
            $validator,
            $transformer,
            $factory,
            $repository,
            $ulidFactory,
        );

        $input = [
            'id' => $this->faker->uuid(),
            'value' => $this->faker->word(),
        ];

        $mutationInput = new UpdateTypeMutationInput();
        $transformer
            ->expects(self::once())
            ->method('transform')
            ->with($input)
            ->willReturn($mutationInput);

        $validator
            ->expects(self::once())
            ->method('validate')
            ->with($mutationInput);

        $type = $this->createMock(CustomerType::class);

        $repository
            ->expects(self::once())
            ->method('find')
            ->with($this->callback(function ($ulid) use ($input) {
                self::assertSame($input['id'], (string) $ulid);

                return true;
            }))
            ->willReturn($type);

        $capturedUpdate = null;

        $factory
            ->expects(self::once())
            ->method('create')
            ->with(self::identicalTo($type), $this->isInstanceOf(CustomerTypeUpdate::class))
            ->willReturnCallback(
                function (CustomerType $typeArg, CustomerTypeUpdate $update) use (&$capturedUpdate) {
                    $capturedUpdate = $update;

                    return new UpdateCustomerTypeCommand($typeArg, $update);
                }
            );

        $commandBus
            ->expects(self::once())
            ->method('dispatch')
            ->with($this->isInstanceOf(UpdateCustomerTypeCommand::class));

        $result = $resolver->__invoke(null, ['args' => ['input' => $input]]);

        self::assertSame($type, $result);
        self::assertInstanceOf(CustomerTypeUpdate::class, $capturedUpdate);
        self::assertSame($input['value'], $capturedUpdate->value);
    }

    public function testInvokeThrowsWhenTypeNotFound(): void
    {
        $commandBus = $this->createMock(CommandBusInterface::class);
        $validator = $this->createMock(MutationInputValidator::class);
        $transformer = $this->createMock(UpdateTypeMutationInputTransformer::class);
        $factory = $this->createMock(UpdateTypeCommandFactoryInterface::class);
        $repository = $this->createMock(TypeRepositoryInterface::class);
        $ulidFactory = new UlidFactory();

        $resolver = new UpdateTypeMutationResolver(
            $commandBus,
            $validator,
            $transformer,
            $factory,
            $repository,
            $ulidFactory,
        );

        $input = [
            'id' => $this->faker->uuid(),
            'value' => $this->faker->word(),
        ];

        $mutationInput = new UpdateTypeMutationInput();

        $transformer
            ->expects(self::once())
            ->method('transform')
            ->with($input)
            ->willReturn($mutationInput);

        $validator
            ->expects(self::once())
            ->method('validate')
            ->with($mutationInput);

        $repository
            ->expects(self::once())
            ->method('find')
            ->with($this->callback(function ($ulid) use ($input) {
                self::assertSame($input['id'], (string) $ulid);

                return true;
            }))
            ->willReturn(null);

        $commandBus
            ->expects(self::never())
            ->method('dispatch');
        $factory
            ->expects(self::never())
            ->method('create');

        $this->expectException(CustomerTypeNotFoundException::class);

        $resolver->__invoke(null, ['args' => ['input' => $input]]);
    }
}
