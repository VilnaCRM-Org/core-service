<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Factory;

use App\Core\Customer\Application\Command\CreateTypeCommand;
use App\Core\Customer\Application\Factory\CreateTypeFactory;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class CreateTypeFactoryTest extends UnitTestCase
{
    public function testCreateTypeCommand(): void
    {
        /** @var CustomerType&MockObject $type */
        $type = $this->createMock(CustomerType::class);

        $factory = new CreateTypeFactory();
        $command = $factory->create($type);

        $this->assertInstanceOf(CreateTypeCommand::class, $command);
        $this->assertSame($type, $command->type);
    }
}
