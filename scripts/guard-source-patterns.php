<?php

declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');

use PhpParser\Error;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;

require __DIR__ . '/../vendor/autoload.php';

error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

const BASELINE_PATH = __DIR__ . '/../config/static-analysis/source-pattern-baseline.json';
const PROJECT_ROOT = __DIR__ . '/..';

/**
 * @return list<array{
 *     type: string,
 *     file: string,
 *     line: int,
 *     message: string
 * }>
 */
function collectViolations(string $rootPath): array
{
    $projectRoot = (string) realpath(PROJECT_ROOT);
    $sourceRoot = (string) realpath($rootPath);
    $parser = (new ParserFactory())->createForNewestSupportedVersion();
    $violations = [];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceRoot, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $fileInfo) {
        if ($fileInfo->getExtension() !== 'php') {
            continue;
        }

        $filePath = $fileInfo->getPathname();
        $relativePath = ltrim(str_replace($projectRoot, '', $filePath), '/');

        try {
            $ast = $parser->parse(file_get_contents($filePath));
        } catch (Error $error) {
            $violations[] = [
                'type' => 'parse_error',
                'file' => $relativePath,
                'line' => $error->getStartLine(),
                'message' => $error->getMessage(),
            ];

            continue;
        }

        if ($ast === null) {
            continue;
        }

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new class($relativePath, $violations) extends NodeVisitorAbstract {
            /**
             * @param list<array{
             *     type: string,
             *     file: string,
             *     line: int,
             *     message: string
             * }> $violations
             */
            public function __construct(
                private readonly string $file,
                private array &$violations
            ) {
            }

            public function enterNode(Node $node): null
            {
                if (
                    $node instanceof Node\Param
                    || $node instanceof Node\Stmt\ClassMethod
                    || $node instanceof Node\Stmt\Function_
                    || $node instanceof Node\Expr\Closure
                    || $node instanceof Node\Expr\ArrowFunction
                    || $node instanceof Node\Stmt\Property
                ) {
                    $type = $node instanceof Node\Stmt\Property ? $node->type : $node->type ?? null;

                    if ($node instanceof Node\Stmt\ClassMethod || $node instanceof Node\Stmt\Function_ || $node instanceof Node\Expr\Closure || $node instanceof Node\Expr\ArrowFunction) {
                        $type = $node->returnType;
                    }

                    if (containsArrayType($type)) {
                        $this->violations[] = [
                            'type' => 'array_type_declaration',
                            'file' => $this->file,
                            'line' => $node->getStartLine(),
                            'message' => 'Native array type declaration found in src/',
                        ];
                    }
                }

                if ($node instanceof Node\Expr\New_ && !$node->class instanceof Node\Stmt\Class_) {
                    if (str_contains($this->file, '/Factory/')) {
                        return null;
                    }

                    $className = $node->class instanceof Node\Name
                        ? $node->class->toString()
                        : 'dynamic-class';

                    if (preg_match('/(?:Exception|Error)$/', $className) === 1) {
                        return null;
                    }

                    if (in_array($className, ['ArrayIterator', 'ArrayObject'], true)) {
                        return null;
                    }

                    $this->violations[] = [
                        'type' => 'hardcoded_new',
                        'file' => $this->file,
                        'line' => $node->getStartLine(),
                        'message' => sprintf('Hardcoded new expression found for "%s"', $className),
                    ];
                }

                return null;
            }
        });

        $traverser->traverse($ast);
    }

    usort(
        $violations,
        static fn (array $left, array $right): int => [$left['file'], $left['line'], $left['type']]
            <=> [$right['file'], $right['line'], $right['type']]
    );

    return $violations;
}

function containsArrayType(Node\Identifier|Node\Name|Node\ComplexType|null $type): bool
{
    if ($type === null) {
        return false;
    }

    if ($type instanceof Node\Identifier) {
        return $type->toLowerString() === 'array';
    }

    if ($type instanceof Node\UnionType || $type instanceof Node\IntersectionType) {
        foreach ($type->types as $innerType) {
            if (containsArrayType($innerType)) {
                return true;
            }
        }
    }

    if ($type instanceof Node\NullableType) {
        return containsArrayType($type->type);
    }

    return false;
}

/**
 * @param list<array{
 *     type: string,
 *     file: string,
 *     line: int,
 *     message: string
 * }> $violations
 *
 * @return list<string>
 */
function violationKeys(array $violations): array
{
    return array_map(
        static fn (array $violation): string => sprintf(
            '%s:%d:%s:%s',
            $violation['file'],
            $violation['line'],
            $violation['type'],
            $violation['message']
        ),
        $violations
    );
}

/**
 * @return list<string>
 */
function readBaseline(string $path): array
{
    if (!file_exists($path)) {
        return [];
    }

    $decoded = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

    return is_array($decoded) ? array_values(array_filter($decoded, 'is_string')) : [];
}

function writeBaseline(string $path, array $keys): void
{
    if (!is_dir(dirname($path))) {
        mkdir(dirname($path), 0777, true);
    }

    file_put_contents(
        $path,
        json_encode(array_values(array_unique($keys)), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL
    );
}

$violations = collectViolations(__DIR__ . '/../src');
$keys = violationKeys($violations);

if (($argv[1] ?? null) === '--generate-baseline') {
    writeBaseline(BASELINE_PATH, $keys);
    fwrite(STDOUT, sprintf("Generated baseline with %d violations.\n", count($keys)));
    exit(0);
}

$baseline = array_flip(readBaseline(BASELINE_PATH));
$newViolations = array_values(
    array_filter(
        $violations,
        static fn (array $violation): bool => !isset($baseline[
            sprintf(
                '%s:%d:%s:%s',
                $violation['file'],
                $violation['line'],
                $violation['type'],
                $violation['message']
            )
        ])
    )
);

if ($newViolations === []) {
    fwrite(STDOUT, "Source pattern guard passed.\n");
    exit(0);
}

fwrite(STDERR, "Source pattern guard found non-baselined violations:\n");

foreach ($newViolations as $violation) {
    fwrite(
        STDERR,
        sprintf(
            "- %s:%d [%s] %s\n",
            $violation['file'],
            $violation['line'],
            $violation['type'],
            $violation['message']
        )
    );
}

exit(1);
