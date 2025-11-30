<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Transformer;

use App\Core\Customer\Application\Transformer\UpdateCustomerMutationInputTransformer;
use App\Tests\Unit\UnitTestCase;

final class UpdateCustomerMutationInputTransformerTest extends UnitTestCase
{
    private UpdateCustomerMutationInputTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transformer = new UpdateCustomerMutationInputTransformer();
    }

    public function testTransformMapsAllValues(): void
    {
        $args = [
            'initials' => $this->faker->lexify('??'),
            'email' => $this->faker->email(),
            'phone' => $this->faker->phoneNumber(),
            'leadSource' => $this->faker->word(),
            'type' => $this->faker->uuid(),
            'status' => $this->faker->uuid(),
            'confirmed' => $this->faker->boolean(),
        ];

        $input = $this->transformer->transform($args);

        self::assertSame($args['initials'], $input->initials);
        self::assertSame($args['email'], $input->email);
        self::assertSame($args['phone'], $input->phone);
        self::assertSame($args['leadSource'], $input->leadSource);
        self::assertSame($args['type'], $input->type);
        self::assertSame($args['status'], $input->status);
        self::assertSame($args['confirmed'], $input->confirmed);
    }

    public function testTransformAllowsMissingValues(): void
    {
        $input = $this->transformer->transform([]);

        self::assertNull($input->initials);
        self::assertNull($input->email);
        self::assertNull($input->phone);
        self::assertNull($input->leadSource);
        self::assertNull($input->type);
        self::assertNull($input->status);
        self::assertNull($input->confirmed);
    }
}
