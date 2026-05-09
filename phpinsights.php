<?php

declare(strict_types=1);

use NunoMaduro\PhpInsights\Domain\Insights\ForbiddenNormalClasses;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Files\LineLengthSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Formatting\SpaceAfterNotSniff;
use SlevomatCodingStandard\Sniffs\Classes\SuperfluousExceptionNamingSniff;
use SlevomatCodingStandard\Sniffs\Classes\SuperfluousInterfaceNamingSniff;
use SlevomatCodingStandard\Sniffs\Functions\UnusedParameterSniff;
use SlevomatCodingStandard\Sniffs\Namespaces\UseSpacingSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\ParameterTypeHintSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\ReturnTypeHintSniff;

return [
    'preset' => 'symfony',
    'ide' => 'phpstorm',
    'exclude' => [
        'vendor',
        'scripts',
        'CLI/bats/php',
        'src/Core/Customer/Application/DTO',
        'src/Core/Onboarding/Application/DTO',
    ],
    'add' => [],
    'remove' => [
        UnusedParameterSniff::class,
        SuperfluousInterfaceNamingSniff::class,
        SuperfluousExceptionNamingSniff::class,
        SpaceAfterNotSniff::class,
        NunoMaduro\PhpInsights\Domain\Sniffs\ForbiddenSetterSniff::class,
        UseSpacingSniff::class,
        NunoMaduro\PhpInsights\Domain\Sniffs\ForbiddenPublicPropertySniff::class,
    ],

    'config' => [
        SlevomatCodingStandard\Sniffs\TypeHints\DisallowMixedTypeHintSniff::class => [
            'exclude' => [
                // Doctrine ODM requires mixed $id in find() method signature
                'src/Core/Customer/Domain/Repository/CustomerRepositoryInterface',
                'src/Core/Customer/Infrastructure/Repository/CachedCustomerRepository',
            ],
        ],
        ParameterTypeHintSniff::class => [
            'exclude' => [
                'src/Shared/Application/Command/SchemathesisCustomerSeeder',
                'src/Shared/Application/Command/SchemathesisCustomerStatusSeeder',
                'src/Shared/Application/Command/SchemathesisCustomerTypeSeeder',
                // Api Platform signatures and Psalm array-shape rules keep these array params docblock-only.
                'src/Core/Onboarding/Application/Command/SeedOnboardingDefaultsCommand',
                'src/Core/Onboarding/Application/Processor/CreateOnboardingStepProcessor',
                'src/Core/Onboarding/Application/Processor/CreateTariffPlanProcessor',
                'src/Core/Onboarding/Application/Processor/OnboardingStepPatchProcessor',
                'src/Core/Onboarding/Application/Processor/OnboardingStepPutProcessor',
                'src/Core/Onboarding/Application/Processor/TariffPlanPatchProcessor',
                'src/Core/Onboarding/Application/Processor/TariffPlanPutProcessor',
                'src/Core/Onboarding/Domain/Factory/TariffPlanDetailsFactory',
                'tests/Unit/Shared/Infrastructure/Bus/CallableFirstParameterExtractorTest',
            ],
        ],
        ReturnTypeHintSniff::class => [
            'exclude' => [
                'src/Shared/Application/Command/SchemathesisCustomerSeeder',
                'src/Shared/Application/Command/SchemathesisCustomerStatusSeeder',
                'src/Shared/Application/Command/SchemathesisCustomerTypeSeeder',
            ],
        ],
        LineLengthSniff::class => [
            'exclude' => [
                'phpinsights',
            ],
            'ignoreComments' => true,
            'lineLimit' => 100,
        ],
        ForbiddenNormalClasses::class => [
            'exclude' => [
                'src/Shared/Infrastructure/Bus/Command/InMemorySymfonyCommandBus',
                'src/Shared/Infrastructure/Bus/Event/InMemorySymfonyEventBus',
                'src/Core/Customer/Domain/Entity/Customer',
            ],
        ],
    ],
    'requirements' => [
        'min-quality' => 100,
        'min-complexity' => 93,
        'min-architecture' => 100,
        'min-style' => 100,
    ],
    'threads' => null,
];
