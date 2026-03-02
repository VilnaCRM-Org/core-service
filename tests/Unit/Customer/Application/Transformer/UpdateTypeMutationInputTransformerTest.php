<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Transformer;

use App\Core\Customer\Application\Transformer\UpdateTypeMutationInputTransformer;
use App\Tests\Unit\UnitTestCase;

final class UpdateTypeMutationInputTransformerTest extends UnitTestCase
{
    private UpdateTypeMutationInputTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transformer = new UpdateTypeMutationInputTransformer();
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
