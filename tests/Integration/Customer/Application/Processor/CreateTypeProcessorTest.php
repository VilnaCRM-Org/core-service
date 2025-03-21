<?php

declare(strict_types=1);

namespace App\Tests\Integration\Customer\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Customer\Application\DTO\TypeCreateDto;
use App\Customer\Application\Processor\CreateTypeProcessor;
use App\Customer\Domain\Entity\CustomerType;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Integration\IntegrationTestCase;

final class CreateTypeProcessorTest extends IntegrationTestCase
{
    private CreateTypeProcessor $processor;
    private CommandBusInterface $commandBus;

    protected function setUp(): void
    {
        parent::setUp();
        $container = $this->getContainer();
        $this->processor = $container->get(CreateTypeProcessor::class);
        $this->commandBus = $container->get(CommandBusInterface::class);
    }

    public function testShouldCreateCustomerTypeSuccessfully(): void
    {
        $dto = new TypeCreateDto('premium');
        $operation = $this->createMock(Operation::class);

        $result = $this->processor->process($dto, $operation);

        $this->assertInstanceOf(CustomerType::class, $result);
        $this->assertEquals('premium', $result->getValue());
        $this->assertNotNull($result->getUlid());
    }
}
