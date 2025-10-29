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
use App\Shared\Application\Validator\MutationInputValidator;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;

final class UpdateTypeMutationResolverTest extends UnitTestCase
{
    public function testInvokeUpdatesType(): void
    {
        $dependencies = $this->setupDependencies();
        $resolver = $this->createResolver($dependencies);
        $input = $this->generateInput();
        $type = $this->createMock(CustomerType::class);

        $this->setupTransformerAndValidator($dependencies, $input);

        $capturedUpdate = null;
        $this->setupFactoryAndCommandBus($dependencies, $type, $capturedUpdate);

        $result = $resolver->__invoke($type, ['args' => ['input' => $input]]);

        self::assertSame($type, $result);
        self::assertInstanceOf(CustomerTypeUpdate::class, $capturedUpdate);
        self::assertSame($input['value'], $capturedUpdate->value);
    }

    public function testInvokeThrowsWhenTypeNotFound(): void
    {
        $dependencies = $this->setupDependencies();
        $resolver = $this->createResolver($dependencies);
        $input = $this->generateInput();

        $this->setupTransformerAndValidator($dependencies, $input);
        $this->expectNeverCalledFactoryAndCommandBus($dependencies);

        $this->expectException(CustomerTypeNotFoundException::class);
        $resolver->__invoke(null, ['args' => ['input' => $input]]);
    }

    public function testInvokeWithIriIdExtractsUlid(): void
    {
        $dependencies = $this->setupDependencies();
        $resolver = $this->createResolver($dependencies);
        $ulid = $this->faker->uuid();
        $input = [
            'id' => '/api/customer_types/' . $ulid,
            'value' => $this->faker->word(),
        ];
        $type = $this->createMock(CustomerType::class);

        $this->setupTransformerAndValidator($dependencies, $input);
        $dependencies['repository']
            ->expects(self::once())
            ->method('find')
            ->with($ulid)
            ->willReturn($type);

        $capturedUpdate = null;
        $this->setupFactoryAndCommandBus($dependencies, $type, $capturedUpdate);

        $result = $resolver->__invoke(null, ['args' => ['input' => $input]]);

        self::assertSame($type, $result);
        self::assertInstanceOf(CustomerTypeUpdate::class, $capturedUpdate);
        self::assertSame($input['value'], $capturedUpdate->value);
    }

    /** @return array<string, \PHPUnit\Framework\MockObject\MockObject> */
    private function setupDependencies(): array
    {
        return [
            'commandBus' => $this->createMock(CommandBusInterface::class),
            'validator' => $this->createMock(MutationInputValidator::class),
            'transformer' => $this->createMock(UpdateTypeMutationInputTransformer::class),
            'factory' => $this->createMock(UpdateTypeCommandFactoryInterface::class),
            'repository' => $this->createMock(TypeRepositoryInterface::class),
        ];
    }

    /** @param array<string, \PHPUnit\Framework\MockObject\MockObject> $deps */
    private function createResolver(array $deps): UpdateTypeMutationResolver
    {
        return new UpdateTypeMutationResolver(
            $deps['commandBus'],
            $deps['validator'],
            $deps['transformer'],
            $deps['factory'],
            $deps['repository'],
        );
    }

    /** @return array<string, string> */
    private function generateInput(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'value' => $this->faker->word(),
        ];
    }

    /**
     * @param array<string, \PHPUnit\Framework\MockObject\MockObject> $deps
     * @param array<string, string> $input
     */
    private function setupTransformerAndValidator(array $deps, array $input): void
    {
        $mutationInput = new UpdateTypeMutationInput();
        $deps['transformer']
            ->expects(self::once())
            ->method('transform')
            ->with($input)
            ->willReturn($mutationInput);

        $deps['validator']
            ->expects(self::once())
            ->method('validate')
            ->with($mutationInput);
    }

    /** @param array<string, \PHPUnit\Framework\MockObject\MockObject> $deps */
    private function setupFactoryAndCommandBus(
        array $deps,
        \PHPUnit\Framework\MockObject\MockObject $type,
        ?CustomerTypeUpdate &$capturedUpdate
    ): void {
        $deps['factory']
            ->expects(self::once())
            ->method('create')
            ->with(
                self::identicalTo($type),
                $this->isInstanceOf(CustomerTypeUpdate::class)
            )
            ->willReturnCallback(
                static function (
                    CustomerType $typeArg,
                    CustomerTypeUpdate $update
                ) use (&$capturedUpdate) {
                    $capturedUpdate = $update;
                    return new UpdateCustomerTypeCommand($typeArg, $update);
                }
            );

        $deps['commandBus']
            ->expects(self::once())
            ->method('dispatch')
            ->with($this->isInstanceOf(UpdateCustomerTypeCommand::class));
    }

    /** @param array<string, \PHPUnit\Framework\MockObject\MockObject> $deps */
    private function expectNeverCalledFactoryAndCommandBus(array $deps): void
    {
        $deps['commandBus']->expects(self::never())->method('dispatch');
        $deps['factory']->expects(self::never())->method('create');
    }
}
