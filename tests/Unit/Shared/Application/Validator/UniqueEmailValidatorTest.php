<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Factory\CustomerFactory;
use App\Core\Customer\Domain\Factory\CustomerFactoryInterface;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Shared\Application\Validator\UniqueEmail;
use App\Shared\Application\Validator\UniqueEmailValidator;
use App\Shared\Infrastructure\Factory\UlidFactory;
use App\Shared\Infrastructure\Transformer\SymfonyUlidBinaryTransformer;
use App\Shared\Infrastructure\Transformer\UlidRepresentationTransformer;
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use App\Shared\Infrastructure\Transformer\UlidValueTransformer;
use App\Shared\Infrastructure\Validator\UlidValidator;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Contracts\Translation\TranslatorInterface;

final class UniqueEmailValidatorTest extends UnitTestCase
{
    private CustomerFactoryInterface $customerFactory;
    private UlidTransformer $transformer;
    private CustomerRepositoryInterface $customerRepository;
    private ExecutionContext $context;
    private TranslatorInterface $translator;
    private RequestStack $requestStack;
    private UniqueEmailValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customerFactory = new CustomerFactory();
        $ulidFactory = new UlidFactory();
        $this->transformer = new UlidTransformer(
            $ulidFactory,
            new UlidValidator(),
            new UlidValueTransformer(
                $ulidFactory,
                new UlidRepresentationTransformer(),
                new SymfonyUlidBinaryTransformer()
            )
        );
        $this->customerRepository =
            $this->createMock(CustomerRepositoryInterface::class);
        $this->context = $this->createMock(ExecutionContext::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->requestStack = new RequestStack();
        $this->validator = new UniqueEmailValidator(
            $this->customerRepository,
            $this->translator,
            $this->requestStack
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

    public function testValidateAllowsCurrentCustomerEmailDuringUpdate(): void
    {
        $email = $this->faker->email();
        $ulid = (string) new Ulid();
        $customer = $this->createCustomer($email, $ulid);
        $request = new Request();
        $request->attributes->set('ulid', $ulid);
        $this->requestStack->push($request);

        $this->customerRepository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($customer);

        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($email, new UniqueEmail());
    }

    private function createCustomer(
        string $email,
        ?string $ulid = null
    ): Customer {
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
            $this->transformer->transformFromSymfonyUlid(
                new Ulid($ulid ?? (string) new Ulid())
            ),
        );
    }

    private function setupValidationExpectations(
        string $email,
        Customer $customer
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
