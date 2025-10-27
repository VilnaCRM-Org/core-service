<?php

declare(strict_types=1);

use NunoMaduro\PhpInsights\Domain\Insights\CyclomaticComplexityIsHigh;
use NunoMaduro\PhpInsights\Domain\Insights\ForbiddenNormalClasses;
use NunoMaduro\PhpInsights\Domain\Sniffs\ForbiddenSetterSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Files\LineLengthSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Strings\UnnecessaryStringConcatSniff;
use SlevomatCodingStandard\Sniffs\Classes\SuperfluousExceptionNamingSniff;
use SlevomatCodingStandard\Sniffs\Classes\SuperfluousInterfaceNamingSniff;
use SlevomatCodingStandard\Sniffs\Functions\UnusedParameterSniff;
use SlevomatCodingStandard\Sniffs\Namespaces\UseSpacingSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\ParameterTypeHintSniff;

return [
    'preset' => 'symfony',
    'ide' => 'phpstorm',
    'exclude' => [
        'vendor',
        'CLI/bats/php',
    ],
    'add' => [],
    'remove' => [
        UnusedParameterSniff::class,
        SuperfluousInterfaceNamingSniff::class,
        SuperfluousExceptionNamingSniff::class,
        ForbiddenSetterSniff::class,
        UseSpacingSniff::class,
    ],
    'config' => [
        ParameterTypeHintSniff::class => [
            'exclude' => [
                'tests/Unit/Shared/Infrastructure/Bus/CallableFirstParameterExtractorTest',
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
            ],
        ],
        UnnecessaryStringConcatSniff::class => [
            'exclude' => [
                'src/Shared/Infrastructure/Bus/Command/CommandNotRegisteredException',
                'src/Shared/Infrastructure/Bus/Event/EventNotRegisteredException',
            ],
        ],
        CyclomaticComplexityIsHigh::class => [
            'exclude' => [
                'src/Shared/Application/Validator/InitialsValidator',
            ],
        ],
    ],
    'requirements' => [
        'min-quality' => 95,
        'min-complexity' => 95,
        'min-architecture' => 90,
        'min-style' => 95,
    ],
    'threads' => null,
];
