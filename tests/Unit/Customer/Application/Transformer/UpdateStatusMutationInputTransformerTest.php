<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Transformer;

use App\Core\Customer\Application\Transformer\UpdateStatusMutationInputTransformer;
use App\Tests\Unit\UnitTestCase;

final class UpdateStatusMutationInputTransformerTest extends UnitTestCase
{
    private UpdateStatusMutationInputTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transformer = new UpdateStatusMutationInputTransformer();
    }

    public function testTransformMapsValue(): void
    {
        $value = $this->faker->word();

        $input = $this->transformer->transform(['value' => $value]);

        self::assertSame($value, $input->value);
    }

    public function testTransformAllowsMissingValue(): void
    {
        $input = $this->transformer->transform([]);

        self::assertNull($input->value);
    }
}
