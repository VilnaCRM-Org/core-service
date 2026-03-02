<?php

declare(strict_types=1);

namespace App\Tests\Integration\Shared;

use ApiPlatform\Validator\Exception\ValidationException;
use App\Core\Customer\Application\MutationInput\CreateTypeMutationInput;
use App\Shared\Application\Validator\LoggingMutationInputValidator;
use App\Shared\Application\Validator\MutationInputValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class MutationInputValidatorTest extends KernelTestCase
{
    public function testValidatesUsingYamlMappings(): void
    {
        self::bootKernel();

        $validator = self::getContainer()->get(MutationInputValidatorInterface::class);
        self::assertInstanceOf(LoggingMutationInputValidator::class, $validator);

        $this->expectException(ValidationException::class);

        $validator->validate(new CreateTypeMutationInput(null));
    }
}
