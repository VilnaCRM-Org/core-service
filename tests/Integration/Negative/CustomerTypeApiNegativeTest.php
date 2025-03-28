<?php

declare(strict_types=1);

namespace App\Tests\Integration\Negative;

use App\Tests\Integration\Negative\Kernel\NegativeKernel;
use Symfony\Component\HttpFoundation\Response;

final class CustomerTypeApiNegativeTest extends BaseNegativeApiTest
{
    protected static function getKernelClass(): string
    {
        return NegativeKernel::class;
    }

    public function testGetCustomerTypesCollection500Error(): void
    {
        $this->sendRequest('GET', '/api/customer_types');
    }

    public function testGetCustomerType500Error(): void
    {
        $typeId = $this->faker->ulid();
        $this->sendRequest('GET', "/api/customer_types/{$typeId}");
    }

    public function testCreateCustomerType500Error(): void
    {
        $payload = [
            'value' => $this->faker->word(),
        ];

        $this->sendRequest(
            'POST',
            '/api/customer_types',
            $payload,
            [],
            Response::HTTP_INTERNAL_SERVER_ERROR,
            'application/ld+json'
        );
    }

    public function testUpdateCustomerType500Error(): void
    {
        $typeId = $this->faker->ulid();
        $payload = [
            'value' => $this->faker->word(),
        ];

        $this->sendRequest(
            'PUT',
            "/api/customer_types/{$typeId}",
            $payload,
            [],
            Response::HTTP_INTERNAL_SERVER_ERROR,
            'application/ld+json'
        );
    }

    public function testDeleteCustomerType500Error(): void
    {
        $typeId = $this->faker->ulid();
        $this->sendRequest('DELETE', "/api/customer_types/{$typeId}");
    }

    public function testPatchCustomerType500Error(): void
    {
        $typeId = $this->faker->ulid();
        $payload = [
            'value' => $this->faker->word(),
        ];

        $this->sendRequest(
            'PATCH',
            "/api/customer_types/{$typeId}",
            $payload,
            [],
            Response::HTTP_INTERNAL_SERVER_ERROR,
            'application/merge-patch+json'
        );
    }
}
