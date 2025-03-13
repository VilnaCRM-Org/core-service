<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Command;

use App\Customer\Application\Command\CreateCustomerCommand;
use App\Customer\Application\Command\CreateCustomerCommandResponse;
use App\Customer\Domain\Entity\Customer;
use App\Customer\Domain\Entity\CustomerStatus;
use App\Customer\Domain\Entity\CustomerType;
use App\Customer\Domain\Factory\CustomerFactory;
use App\Customer\Domain\Factory\CustomerFactoryInterface;
use App\Shared\Infrastructure\Factory\UlidFactory;
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use App\Tests\Unit\UnitTestCase;

/**
 * @internal
 */
final class CreateCustomerCommandTest extends UnitTestCase
{
    private CustomerFactoryInterface $customerFactory;
    private UlidTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customerFactory = new CustomerFactory();
        $this->transformer = new UlidTransformer(new UlidFactory());
    }

    public function testConstructor(): void
    {
        $params = $this->getCommandParams();
        $command = $this->createCommand($params);
        $this->assertInstanceOf(CreateCustomerCommand::class, $command);
        $this->assertSame($params['initials'], $command->initials);
        $this->assertSame($params['email'], $command->email);
        $this->assertSame($params['phone'], $command->phone);
        $this->assertSame($params['leadSource'], $command->leadSource);
        $this->assertSame($params['type'], $command->type);
        $this->assertSame($params['status'], $command->status);
        $this->assertSame($params['confirmed'], $command->confirmed);
    }

    public function testGetResponse(): void
    {
        $params = $this->getCommandParams();
        $customer = $this->createCustomer($params);
        $command = $this->createCommand($params);
        $response = new CreateCustomerCommandResponse($customer);
        $command->setResponse($response);
        $this->assertSame($response, $command->getResponse());
        $this->assertSame($customer, $command->getResponse()->customer);
    }

    /**
     * Get parameters for creating a command.
     *
     * @return array{
     *     initials: string,
     *     email: string,
     *     phone: string,
     *     leadSource: string,
     *     type: \PHPUnit\Framework\MockObject\MockObject,
     *     status: \PHPUnit\Framework\MockObject\MockObject,
     *     confirmed: bool
     * }
     */
    private function getCommandParams(): array
    {
        return [
            'initials' => $this->faker->name(),
            'email' => $this->faker->email(),
            'phone' => $this->faker->phoneNumber(),
            'leadSource' => $this->faker->word(),
            'type' => $this->createMock(CustomerType::class),
            'status' => $this->createMock(CustomerStatus::class),
            'confirmed' => $this->faker->boolean(),
        ];
    }

    /**
     * Create a customer using the provided parameters.
     *
     * @param array{
     *     initials: string,
     *     email: string,
     *     phone: string,
     *     leadSource: string,
     *     type: \PHPUnit\Framework\MockObject\MockObject,
     *     status: \PHPUnit\Framework\MockObject\MockObject,
     *     confirmed: bool
     * } $params
     */
    private function createCustomer(array $params): Customer
    {
        $ulid = $this->faker->ulid();
        return $this->customerFactory->create(
            $params['initials'],
            $params['email'],
            $params['phone'],
            $params['leadSource'],
            $params['type'],
            $params['status'],
            $params['confirmed'],
            $this->transformer->transformFromSymfonyUlid($ulid)
        );
    }

    /**
     * Create a command using the provided parameters.
     *
     * @param array{
     *     initials: string,
     *     email: string,
     *     phone: string,
     *     leadSource: string,
     *     type: \PHPUnit\Framework\MockObject\MockObject,
     *     status: \PHPUnit\Framework\MockObject\MockObject,
     *     confirmed: bool
     * } $params
     */
    private function createCommand(array $params): CreateCustomerCommand
    {
        return new CreateCustomerCommand(
            $params['initials'],
            $params['email'],
            $params['phone'],
            $params['leadSource'],
            $params['type'],
            $params['status'],
            $params['confirmed']
        );
    }
}
