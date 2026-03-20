<?php

declare(strict_types=1);

use NunoMaduro\PhpInsights\Domain\Insights\CyclomaticComplexityIsHigh;
use NunoMaduro\PhpInsights\Domain\Insights\ForbiddenNormalClasses;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Files\LineLengthSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Formatting\SpaceAfterNotSniff;
use SlevomatCodingStandard\Sniffs\Classes\SuperfluousExceptionNamingSniff;
use SlevomatCodingStandard\Sniffs\Classes\SuperfluousInterfaceNamingSniff;
use SlevomatCodingStandard\Sniffs\Functions\FunctionLengthSniff;
use SlevomatCodingStandard\Sniffs\Functions\UnusedParameterSniff;
use SlevomatCodingStandard\Sniffs\Namespaces\UseSpacingSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\ParameterTypeHintSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\ReturnTypeHintSniff;

return [
    'preset' => 'symfony',
    'ide' => 'phpstorm',
    'exclude' => [
        'vendor',
        'CLI/bats/php',
        'src/Core/Customer/Application/DTO',
        'scripts/codespaces',
        'scripts/fix-openapi-spec.php',
        'scripts/validate-openapi-spec.sh',
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
                // OpenAPI processors need mixed return types for schema normalization
                'src/Shared/Application/OpenApi/Processor/CustomerUlidRefReplacer',
                'src/Shared/Application/OpenApi/Processor/SchemaNormalizer',
                'src/Shared/Application/OpenApi/Processor/ConstraintViolationPayloadItemsProcessor',
            ],
        ],
        ParameterTypeHintSniff::class => [
            'exclude' => [
                'tests/Unit/Shared/Infrastructure/Bus/CallableFirstParameterExtractorTest',
                // OpenApiFixer has complex parameter structures
                'src/Shared/Application/OpenApi/Processor/OpenApiFixer',
                'src/Shared/Application/OpenApi/Processor/HydraAllOfItemUpdater',
                'src/Shared/Application/OpenApi/Processor/ConstraintViolationPayloadItemsProcessor',
            ],
        ],
        ReturnTypeHintSniff::class => [
            'exclude' => [
                // OpenAPI processors have complex return types
                'src/Shared/Application/OpenApi/Processor/HydraAllOfItemUpdater',
            ],
        ],
        LineLengthSniff::class => [
            'exclude' => [
                'phpinsights',
                // OpenApiFixer.php has long lines due to error messages and @infection-ignore-line annotations
                'src/Shared/Application/OpenApi/Processor/OpenApiFixer',
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
        CyclomaticComplexityIsHigh::class => [
            'exclude' => [
                // OpenAPI spec fixers have intentional complexity for processing nested structures
                'src/Shared/Application/OpenApi/Processor/OpenApiFixer',
                'src/Shared/Application/OpenApi/Processor/ConstraintViolationPayloadItemsProcessor',
                'src/Shared/Application/OpenApi/Processor/ConstraintViolationPayloadItemsUpdater',
                'src/Shared/Application/OpenApi/Processor/HydraAllOfItemUpdater',
                'src/Shared/Application/OpenApi/Processor/OpenApiSchemaFixesProcessor',
                'src/Shared/Application/OpenApi/Processor/HydraAllOfUpdater',
                'src/Shared/Application/OpenApi/Processor/PayloadItemsRequirementChecker',
            ],
        ],
        FunctionLengthSniff::class => [
            'exclude' => [
                // OpenAPI processors have longer functions due to schema processing requirements
                'src/Shared/Application/OpenApi/Processor/OpenApiFixer',
                'src/Shared/Application/OpenApi/Processor/ConstraintViolationPayloadItemsProcessor',
                'src/Shared/Application/OpenApi/Processor/HydraAllOfItemUpdater',
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
