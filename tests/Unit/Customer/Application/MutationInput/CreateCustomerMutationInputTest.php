<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\MutationInput;

use App\Core\Customer\Application\MutationInput\CreateCustomerMutationInput;
use App\Tests\Unit\UnitTestCase;

final class CreateCustomerMutationInputTest extends UnitTestCase
{
    public function testConstructorAssignsProvidedValues(): void
    {
        $testData = $this->generateTestData();
        $input = $this->createInputWithTestData($testData);

        $this->assertInputMatchesTestData($input, $testData);
    }

    public function testConstructorDefaultsToNull(): void
    {
        $input = new CreateCustomerMutationInput();

        $this->assertAllFieldsAreNull($input);
    }

    /** @return array<string, string|bool> */
    private function generateTestData(): array
    {
        return [
            'initials' => $this->faker->lexify('??'),
            'email' => $this->faker->email(),
            'phone' => $this->faker->phoneNumber(),
            'leadSource' => $this->faker->word(),
            'type' => $this->faker->uuid(),
            'status' => $this->faker->uuid(),
            'confirmed' => $this->faker->boolean(),
        ];
    }

    /** @param array<string, string|bool> $data */
    private function createInputWithTestData(array $data): CreateCustomerMutationInput
    {
        return new CreateCustomerMutationInput(
            $data['initials'],
            $data['email'],
            $data['phone'],
            $data['leadSource'],
            $data['type'],
            $data['status'],
            $data['confirmed']
        );
    }

    /** @param array<string, string|bool> $data */
    private function assertInputMatchesTestData(
        CreateCustomerMutationInput $input,
        array $data
    ): void {
        self::assertSame($data['initials'], $input->initials);
        self::assertSame($data['email'], $input->email);
        self::assertSame($data['phone'], $input->phone);
        self::assertSame($data['leadSource'], $input->leadSource);
        self::assertSame($data['type'], $input->type);
        self::assertSame($data['status'], $input->status);
        self::assertSame($data['confirmed'], $input->confirmed);
    }

    private function assertAllFieldsAreNull(CreateCustomerMutationInput $input): void
    {
        self::assertNull($input->initials);
        self::assertNull($input->email);
        self::assertNull($input->phone);
        self::assertNull($input->leadSource);
        self::assertNull($input->type);
        self::assertNull($input->status);
        self::assertNull($input->confirmed);
    }
}
