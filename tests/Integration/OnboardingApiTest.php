<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Core\Onboarding\Domain\Repository\TariffPlanRepositoryInterface;
use App\Shared\Infrastructure\Factory\UlidFactory;

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

    public function testTariffPlanPatchCanClearUserLimit(): void
    {
        $iri = $this->createEntity('/api/tariff_plans', $this->freePlanPayload());

        $client = self::createClient();
        self::assertSame(50, $client->request('GET', $iri)->toArray()['userLimit']);

        $client->request(
            'PATCH',
            $iri,
            [
                'headers' => ['Content-Type' => 'application/merge-patch+json'],
                'body' => json_encode(['userLimit' => null]),
            ]
        );

        $this->assertResponseIsSuccessful();

        $repository = self::getContainer()->get(TariffPlanRepositoryInterface::class);
        $ulidFactory = self::getContainer()->get(UlidFactory::class);
        $plan = $repository->findByUlid($ulidFactory->create(basename($iri)));

        self::assertNotNull($plan);
        self::assertNull($plan->getUserLimit());
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

    public function testTariffPlanValidationConstraints(): void
    {
        $client = self::createClient();

        $this->assertInvalidTariffPlanPayload($client, ['code' => 'Invalid-Code'], 'code:');
        $this->assertInvalidTariffPlanPayload($client, ['priceCurrency' => 'us'], 'priceCurrency:');
        $this->assertInvalidTariffPlanPayload($client, ['pricePeriod' => 'weekly'], 'pricePeriod:');
        $this->assertInvalidTariffPlanPayload($client, ['position' => 0], 'position:');
        $this->assertInvalidTariffPlanPayload($client, ['userLimit' => 0], 'userLimit:');
        $this->assertInvalidTariffPlanPayload($client, ['deploymentOptions' => []], 'deploymentOptions:');
        $this->assertInvalidTariffPlanPayload(
            $client,
            ['deploymentOptions' => array_map(
                static fn (int $number): string => sprintf('option_%d', $number),
                range(1, 11)
            )],
            'deploymentOptions:'
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

    /**
     * @param array<string, int|string|bool|array|null> $overrides
     */
    private function assertInvalidTariffPlanPayload(
        Client $client,
        array $overrides,
        string $detailFragment
    ): void {
        $client->request(
            'POST',
            '/api/tariff_plans',
            [
                'headers' => ['Content-Type' => 'application/ld+json'],
                'body' => json_encode(array_replace($this->freePlanPayload(), $overrides)),
            ]
        );

        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            $detailFragment,
            $client->getResponse()->toArray(false)['detail']
        );
    }
}
