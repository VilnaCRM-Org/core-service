<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator;

use App\Customer\Domain\Entity\CustomerStatus;
use App\Customer\Domain\Entity\CustomerType;
use App\Customer\Domain\Factory\CustomerFactory;
use App\Customer\Domain\Factory\CustomerFactoryInterface;
use App\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Shared\Application\Validator\UniqueEmail;
use App\Shared\Application\Validator\UniqueEmailValidator;
use App\Shared\Infrastructure\Factory\UlidFactory;
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Contracts\Translation\TranslatorInterface;

final class UniqueEmailValidatorTest extends UnitTestCase
{
    private CustomerFactoryInterface $userFactory;
    private UlidTransformer $transformer;
    private CustomerRepositoryInterface $userRepository;
    private ExecutionContext $context;
    private TranslatorInterface $translator;
    private UniqueEmailValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = new CustomerFactory();
        $this->transformer = new UlidTransformer(new UlidFactory());
        $this->userRepository =
            $this->createMock(CustomerRepositoryInterface::class);
        $this->context = $this->createMock(ExecutionContext::class);

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->validator = new UniqueEmailValidator(
            $this->userRepository,
            $this->translator
        );
        $this->validator->initialize($this->context);
    }

    public function testValidate(): void
    {
        $email = $this->faker->email();
        $user = $this->createCustomer($email);

        $this->setupValidationExpectations($email, $user);

        $this->validator->validate($email, new UniqueEmail());
    }

    public function testNull(): void
    {
        $this->context->expects($this->never())->method('buildViolation');
        $this->validator->validate(null, new UniqueEmail());
    }

    private function createCustomer(string $email): object
    {
        $customerType = $this->createMock(CustomerType::class);
        $customerStatus = $this->createMock(CustomerStatus::class);

        return $this->userFactory->create(
            $this->faker->name(),
            $email,
            $this->faker->phoneNumber(),
            $this->faker->name(),
            $customerType,
            $customerStatus,
            true,
            $this->transformer->transformFromSymfonyUlid($this->faker->ulid()),
        );
    }

    private function setupValidationExpectations(
        string $email,
        object $user
    ): string {
        $errorMessage = $this->faker->word();

        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $this->translator->expects($this->once())
            ->method('trans')
            ->willReturn($errorMessage);

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($errorMessage);

        return $errorMessage;
    }
}
