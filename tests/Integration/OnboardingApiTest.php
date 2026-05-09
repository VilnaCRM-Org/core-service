<?php

declare(strict_types=1);

namespace App\Tests\Integration;

final class OnboardingApiTest extends BaseApiCase
{
    public function testOnboardingStepRestCrudAndFiltering(): void
    {
        $payload = [
            'code' => 'tariff_plan',
            'label' => 'Tariff plan',
            'position' => 1,
            'enabled' => true,
        ];
        $iri = $this->createEntity('/api/onboarding_steps', $payload);

        $client = self::createClient();
        $client->request(
            'PATCH',
            $iri,
            [
                'headers' => ['Content-Type' => 'application/merge-patch+json'],
                'body' => json_encode(['label' => 'Choose tariff plan']),
            ]
        );

        $this->assertResponseIsSuccessful();
        $response = $client->request('GET', $iri);
        $this->assertSame('Choose tariff plan', $response->toArray()['label']);

        $response = $client->request('GET', '/api/onboarding_steps', [
            'query' => [
                'code' => 'tariff_plan',
                'enabled' => true,
                'order' => ['position' => 'asc'],
            ],
        ]);
        $data = $response->toArray();

        $this->assertResponseIsSuccessful();
        $this->assertNotEmpty($data['member']);
        $this->assertSame('tariff_plan', $data['member'][0]['code']);
    }

    public function testTariffPlanRestCrudAndFiltering(): void
    {
        $payload = $this->freePlanPayload();
        $iri = $this->createEntity('/api/tariff_plans', $payload);

        $client = self::createClient();
        $client->request(
            'PUT',
            $iri,
            [
                'headers' => ['Content-Type' => 'application/ld+json'],
                'body' => json_encode($this->corporatePlanPayload()),
            ]
        );

        $this->assertResponseIsSuccessful();
        $response = $client->request('GET', $iri);
        $this->assertSame('Corporate rate', $response->toArray()['name']);

        $response = $client->request('GET', '/api/tariff_plans', [
            'query' => [
                'code' => 'corporate',
                'enabled' => true,
                'priceCents' => ['gte' => 1000],
                'order' => ['position' => 'asc'],
            ],
        ]);
        $data = $response->toArray();

        $this->assertResponseIsSuccessful();
        $this->assertNotEmpty($data['member']);
        $this->assertSame('corporate', $data['member'][0]['code']);
    }

    public function testTariffPlanValidationFailure(): void
    {
        $client = self::createClient();
        $client->request(
            'POST',
            '/api/tariff_plans',
            [
                'headers' => ['Content-Type' => 'application/ld+json'],
                'body' => json_encode([]),
            ]
        );

        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'code: This value should not be blank',
            $client->getResponse()->toArray(false)['detail']
        );
    }

    /**
     * @return array<string, int|string|bool|array|null>
     */
    private function freePlanPayload(): array
    {
        return [
            'code' => 'free',
            'name' => 'Free rate',
            'description' => 'Cloud solution with no functional limitations.',
            'deploymentOptions' => ['cloud'],
            'functionalLimitations' => false,
            'userLimit' => 50,
            'priceCents' => 0,
            'priceCurrency' => 'USD',
            'pricePeriod' => 'none',
            'position' => 1,
            'enabled' => true,
        ];
    }

    /**
     * @return array<string, int|string|bool|array|null>
     */
    private function corporatePlanPayload(): array
    {
        return [
            'code' => 'corporate',
            'name' => 'Corporate rate',
            'description' => 'Cloud and box solutions without a user limit.',
            'deploymentOptions' => ['cloud', 'box'],
            'functionalLimitations' => false,
            'userLimit' => null,
            'priceCents' => 1000,
            'priceCurrency' => 'USD',
            'pricePeriod' => 'per_user',
            'position' => 2,
            'enabled' => true,
        ];
    }
}
