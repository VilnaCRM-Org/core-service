<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Transformer;

use App\Core\Customer\Application\Transformer\CreateStatusMutationInputTransformer;
use App\Tests\Unit\UnitTestCase;

final class CreateStatusMutationInputTransformerTest extends UnitTestCase
{
    private CreateStatusMutationInputTransformer $transformer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->transformer = new CreateStatusMutationInputTransformer();
    }

    public function testTransformMapsValue(): void
    {
        $value = $this->faker->word();

        $input = $this->transformer->transform(['value' => $value]);

        self::assertSame($value, $input->value);
    }

    public function testTransformWithoutValue(): void
    {
        $input = $this->transformer->transform([]);

        self::assertNull($input->value);
    }
}
