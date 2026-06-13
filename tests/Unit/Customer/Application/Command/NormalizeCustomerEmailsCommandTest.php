<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Command;

use App\Core\Customer\Application\Command\NormalizeCustomerEmailsCommand;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

final class NormalizeCustomerEmailsCommandTest extends UnitTestCase
{
    private CustomerRepositoryInterface&MockObject $repository;
    private CommandTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(CustomerRepositoryInterface::class);

        $command = new NormalizeCustomerEmailsCommand($this->repository);
        $application = new Application();
        $application->add($command);

        $this->tester = new CommandTester(
            $application->find('customer:emails:normalize')
        );
    }

    /**
     * Wrap customers in a generator so the test asserts the command streams
     * the cursor lazily rather than materialising an array.
     *
     * @param Customer ...$customers
     *
     * @return \Generator<int, Customer>
     */
    private function customerStream(Customer ...$customers): \Generator
    {
        yield from $customers;
    }

    public function testNormalizesMixedCaseEmail(): void
    {
        $customer = $this->createMock(Customer::class);
        $customer->method('getEmail')->willReturn('MiXeD@Example.COM');
        $customer->method('getUlid')->willReturn('ulid-1');

        $this->repository
            ->expects($this->once())
            ->method('findAllIterable')
            ->willReturn($this->customerStream($customer));

        $this->repository
            ->expects($this->once())
            ->method('findByEmail')
            ->with('mixed@example.com')
            ->willReturn(null);

        $customer
            ->expects($this->once())
            ->method('setEmail')
            ->with('mixed@example.com');

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($customer);

        $this->tester->execute([]);

        $this->tester->assertCommandIsSuccessful();
        $output = $this->tester->getDisplay();
        self::assertStringContainsString('Normalized 1 customer email(s)', $output);
        self::assertStringContainsString('skipped 0 conflicting record(s)', $output);
    }

    public function testSkipsAlreadyNormalizedEmail(): void
    {
        $customer = $this->createMock(Customer::class);
        $customer->method('getEmail')->willReturn('already@example.com');

        $this->repository
            ->expects($this->once())
            ->method('findAllIterable')
            ->willReturn($this->customerStream($customer));

        $this->repository
            ->expects($this->never())
            ->method('findByEmail');

        $customer->expects($this->never())->method('setEmail');
        $this->repository->expects($this->never())->method('save');

        $this->tester->execute([]);

        $this->tester->assertCommandIsSuccessful();
        $output = $this->tester->getDisplay();
        self::assertStringContainsString('Normalized 0 customer email(s)', $output);
    }

    public function testSkipsConflictingEmailWithoutCrashing(): void
    {
        $customer = $this->createMock(Customer::class);
        $customer->method('getEmail')->willReturn('Clash@Example.COM');
        $customer->method('getUlid')->willReturn('ulid-source');

        $conflicting = $this->createMock(Customer::class);
        $conflicting->method('getUlid')->willReturn('ulid-other');

        $this->repository
            ->expects($this->once())
            ->method('findAllIterable')
            ->willReturn($this->customerStream($customer));

        $this->repository
            ->expects($this->once())
            ->method('findByEmail')
            ->with('clash@example.com')
            ->willReturn($conflicting);

        $customer->expects($this->never())->method('setEmail');
        $this->repository->expects($this->never())->method('save');

        $this->tester->execute([]);

        $this->tester->assertCommandIsSuccessful();
        $output = $this->tester->getDisplay();
        self::assertStringContainsString('Skipped customer "ulid-source"', $output);
        self::assertStringContainsString('skipped 1 conflicting record(s)', $output);
    }

    public function testNormalizesWhenLookupResolvesSameCustomer(): void
    {
        // findByEmail may resolve the very record being normalised (e.g. when a
        // stale lowercase row points at the same ULID); that is NOT a conflict.
        $customer = $this->createMock(Customer::class);
        $customer->method('getEmail')->willReturn('Self@Example.COM');
        $customer->method('getUlid')->willReturn('ulid-self');

        $this->repository
            ->expects($this->once())
            ->method('findAllIterable')
            ->willReturn($this->customerStream($customer));

        $this->repository
            ->expects($this->once())
            ->method('findByEmail')
            ->with('self@example.com')
            ->willReturn($customer);

        $customer
            ->expects($this->once())
            ->method('setEmail')
            ->with('self@example.com');

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($customer);

        $this->tester->execute([]);

        $this->tester->assertCommandIsSuccessful();
        self::assertStringContainsString(
            'Normalized 1 customer email(s)',
            $this->tester->getDisplay()
        );
    }
}
