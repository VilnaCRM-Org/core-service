<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator;

use App\Core\Customer\Domain\Entity\CustomerInterface;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Factory\CustomerFactory;
use App\Core\Customer\Domain\Factory\CustomerFactoryInterface;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Shared\Application\Validator\UniqueEmail;
use App\Shared\Application\Validator\UniqueEmailValidator;
use App\Shared\Infrastructure\Factory\UlidFactory;
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Contracts\Translation\TranslatorInterface;

final class UniqueEmailValidatorTest extends UnitTestCase
{
    private CustomerFactoryInterface $customerFactory;
    private UlidTransformer $transformer;
    private CustomerRepositoryInterface $customerRepository;
    private ExecutionContext $context;
    private TranslatorInterface $translator;
    private UniqueEmailValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customerFactory = new CustomerFactory();
        $this->transformer = new UlidTransformer(new UlidFactory());
        $this->customerRepository =
            $this->createMock(CustomerRepositoryInterface::class);
        $this->context = $this->createMock(ExecutionContext::class);

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->validator = new UniqueEmailValidator(
            $this->customerRepository,
            $this->translator
        );
        $this->validator->initialize($this->context);
    }

    public function testValidate(): void
    {
        $email = $this->faker->email();
        $customer = $this->createCustomer($email);

        $this->setupValidationExpectations($email, $customer);

        $this->validator->validate($email, new UniqueEmail());
    }

    public function testNull(): void
    {
        $this->context->expects($this->never())->method('buildViolation');
        $this->validator->validate(null, new UniqueEmail());
    }

    public function testValidateNonExistingEmail(): void
    {
        $email = $this->faker->email();
        $constraint = new UniqueEmail();

        $this->customerRepository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);

        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($email, $constraint);
    }

    private function createCustomer(string $email): \App\Core\Customer\Domain\Entity\Customer
    {
        $customerType = $this->createMock(CustomerType::class);
        $customerStatus = $this->createMock(CustomerStatus::class);

        return $this->customerFactory->create(
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
        CustomerInterface $customer
    ): void {
        $errorMessage = $this->faker->word();

        $this->customerRepository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($customer);

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('email.not.unique')
            ->willReturn($errorMessage);

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($errorMessage);
    }
}
