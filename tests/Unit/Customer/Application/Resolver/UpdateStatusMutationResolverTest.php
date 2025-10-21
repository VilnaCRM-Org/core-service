<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Resolver;

use App\Core\Customer\Application\Command\UpdateCustomerStatusCommand;
use App\Core\Customer\Application\Factory\UpdateStatusCommandFactoryInterface;
use App\Core\Customer\Application\MutationInput\UpdateStatusMutationInput;
use App\Core\Customer\Application\Resolver\UpdateStatusMutationResolver;
use App\Core\Customer\Application\Transformer\UpdateStatusMutationInputTransformer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Exception\CustomerStatusNotFoundException;
use App\Core\Customer\Domain\Repository\StatusRepositoryInterface;
use App\Core\Customer\Domain\ValueObject\CustomerStatusUpdate;
use App\Shared\Application\Validator\MutationInputValidator;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Factory\UlidFactory;
use App\Tests\Unit\UnitTestCase;

final class UpdateStatusMutationResolverTest extends UnitTestCase
{
    public function testInvokeUpdatesStatus(): void
    {
        $dependencies = $this->setupDependencies();
        $resolver = $this->createResolver($dependencies);
        $input = $this->generateInput();

        $this->setupTransformerAndValidator($dependencies, $input);
        $status = $this->createMock(CustomerStatus::class);
        $this->setupRepositoryToReturnStatus($dependencies['repository'], $input, $status);

        $capturedUpdate = null;
        $this->setupFactoryAndCommandBus($dependencies, $status, $capturedUpdate);

        $result = $resolver->__invoke(null, ['args' => ['input' => $input]]);

        self::assertSame($status, $result);
        self::assertInstanceOf(CustomerStatusUpdate::class, $capturedUpdate);
        self::assertSame($input['value'], $capturedUpdate->value);
    }

    public function testInvokeThrowsWhenStatusNotFound(): void
    {
        $dependencies = $this->setupDependencies();
        $resolver = $this->createResolver($dependencies);
        $input = $this->generateInput();

        $this->setupTransformerAndValidator($dependencies, $input);
        $this->setupRepositoryToReturnNull($dependencies['repository'], $input);
        $this->expectNeverCalledFactoryAndCommandBus($dependencies);

        $this->expectException(CustomerStatusNotFoundException::class);
        $resolver->__invoke(null, ['args' => ['input' => $input]]);
    }

    /** @return array<string, \PHPUnit\Framework\MockObject\MockObject|UlidFactory> */
    private function setupDependencies(): array
    {
        return [
            'commandBus' => $this->createMock(CommandBusInterface::class),
            'validator' => $this->createMock(MutationInputValidator::class),
            'transformer' => $this->createMock(UpdateStatusMutationInputTransformer::class),
            'factory' => $this->createMock(UpdateStatusCommandFactoryInterface::class),
            'repository' => $this->createMock(StatusRepositoryInterface::class),
            'ulidFactory' => new UlidFactory(),
        ];
    }

    /** @param array<string, \PHPUnit\Framework\MockObject\MockObject|UlidFactory> $deps */
    private function createResolver(array $deps): UpdateStatusMutationResolver
    {
        return new UpdateStatusMutationResolver(
            $deps['commandBus'],
            $deps['validator'],
            $deps['transformer'],
            $deps['factory'],
            $deps['repository'],
            $deps['ulidFactory'],
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
     * @param array<string, \PHPUnit\Framework\MockObject\MockObject|UlidFactory> $deps
     * @param array<string, string> $input
     */
    private function setupTransformerAndValidator(array $deps, array $input): void
    {
        $mutationInput = new UpdateStatusMutationInput();
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

    /** @param array<string, string> $input */
    private function setupRepositoryToReturnStatus(
        \PHPUnit\Framework\MockObject\MockObject $repository,
        array $input,
        \PHPUnit\Framework\MockObject\MockObject $status
    ): void {
        $repository
            ->expects(self::once())
            ->method('find')
            ->with($this->callback(static function ($ulid) use ($input) {
                self::assertSame($input['id'], (string) $ulid);
                return true;
            }))
            ->willReturn($status);
    }

    /** @param array<string, string> $input */
    private function setupRepositoryToReturnNull(
        \PHPUnit\Framework\MockObject\MockObject $repository,
        array $input
    ): void {
        $repository
            ->expects(self::once())
            ->method('find')
            ->with($this->callback(static function ($ulid) use ($input) {
                self::assertSame($input['id'], (string) $ulid);
                return true;
            }))
            ->willReturn(null);
    }

    /** @param array<string, \PHPUnit\Framework\MockObject\MockObject|UlidFactory> $deps */
    private function setupFactoryAndCommandBus(
        array $deps,
        \PHPUnit\Framework\MockObject\MockObject $status,
        ?CustomerStatusUpdate &$capturedUpdate
    ): void {
        $deps['factory']
            ->expects(self::once())
            ->method('create')
            ->with(
                self::identicalTo($status),
                $this->isInstanceOf(CustomerStatusUpdate::class)
            )
            ->willReturnCallback(
                static function (
                    CustomerStatus $statusArg,
                    CustomerStatusUpdate $update
                ) use (&$capturedUpdate) {
                    $capturedUpdate = $update;
                    return new UpdateCustomerStatusCommand($statusArg, $update);
                }
            );

        $deps['commandBus']
            ->expects(self::once())
            ->method('dispatch')
            ->with($this->isInstanceOf(UpdateCustomerStatusCommand::class));
    }

    /** @param array<string, \PHPUnit\Framework\MockObject\MockObject|UlidFactory> $deps */
    private function expectNeverCalledFactoryAndCommandBus(array $deps): void
    {
        $deps['commandBus']->expects(self::never())->method('dispatch');
        $deps['factory']->expects(self::never())->method('create');
    }
}
