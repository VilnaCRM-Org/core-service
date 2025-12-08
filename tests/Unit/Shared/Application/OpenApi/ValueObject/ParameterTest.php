<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\ValueObject;

use App\Shared\Application\OpenApi\ValueObject\Parameter;
use App\Tests\Unit\UnitTestCase;

final class ParameterTest extends UnitTestCase
{
    public function testCreateWithValidData(): void
    {
        $name = $this->faker->name();
        $type = $this->faker->word();
        $example = $this->faker->word();

        $parameter = new Parameter($name, $type, $example);

        $this->assertEquals($name, $parameter->name);
        $this->assertEquals($type, $parameter->type);
        $this->assertEquals($example, $parameter->example);
    }

    public function testRequiredCreatesParameterWithRequiredTrue(): void
    {
        $name = $this->faker->name();
        $type = $this->faker->word();
        $example = $this->faker->word();

        $parameter = Parameter::required($name, $type, $example);

        $this->assertEquals($name, $parameter->name);
        $this->assertEquals($type, $parameter->type);
        $this->assertEquals($example, $parameter->example);
        $this->assertTrue($parameter->isRequired());
    }

    public function testOptionalCreatesParameterWithRequiredFalse(): void
    {
        $name = $this->faker->name();
        $type = $this->faker->word();
        $example = $this->faker->word();

        $parameter = Parameter::optional($name, $type, $example);

        $this->assertEquals($name, $parameter->name);
        $this->assertEquals($type, $parameter->type);
        $this->assertEquals($example, $parameter->example);
        $this->assertFalse($parameter->isRequired());
    }
}
