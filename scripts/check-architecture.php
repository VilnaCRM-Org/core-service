#!/usr/bin/env php
<?php

declare(strict_types=1);

use PhpParser\Error;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;

require dirname(__DIR__) . '/vendor/autoload.php';

final class ArchitectureGuard
{
    private const SOURCE_DIRECTORY = 'src';
    private const FACTORY_DIRECTORY = DIRECTORY_SEPARATOR . 'Factory' . DIRECTORY_SEPARATOR;
    private const NATIVE_ARRAY_MESSAGE =
        'Use Psalm array shapes/docblocks or typed collections instead of native array declarations in src/.';

    /** @var list<string> */
    private array $suppressedDirectories = [
        'src/Core/Customer/Application',
        'src/Core/Customer/Domain',
        'src/Core/Customer/Infrastructure',
        'src/Internal/HealthCheck/Application',
        'src/Shared/Application/Extractor',
        'src/Shared/Application/Observability',
        'src/Shared/Application/OpenApi',
        'src/Shared/Application/Validator',
        'src/Shared/Domain/Aggregate',
        'src/Shared/Domain/Bus',
        'src/Shared/Domain/ValueObject',
        'src/Shared/Infrastructure/Bus',
        'src/Shared/Infrastructure/Cache',
        'src/Shared/Infrastructure/Filter',
        'src/Shared/Infrastructure/Observability',
        'src/Shared/Infrastructure/Transformer',
    ];

    public function __construct(
        private readonly string $projectRoot
    ) {
    }

    public function run(): int
    {
        $violations = [];
        $parser = (new ParserFactory())->createForHostVersion();

        foreach ($this->phpFiles($this->projectRoot . DIRECTORY_SEPARATOR . self::SOURCE_DIRECTORY) as $filePath) {
            $relativePath = $this->relativePath($filePath);

            if ($this->isSuppressedSource($relativePath)) {
                continue;
            }

            $contents = file_get_contents($filePath);
            if ($contents === false) {
                $violations[] = sprintf('ParseError: Unable to read %s', $relativePath);

                continue;
            }

            try {
                $statements = $parser->parse($contents);
            } catch (Error $error) {
                $violations[] = sprintf(
                    'ParseError: %s (%s:%d)',
                    $error->getRawMessage(),
                    $relativePath,
                    $error->getStartLine()
                );

                continue;
            }

            if ($statements === null) {
                continue;
            }

            $nameResolver = new NodeTraverser();
            $nameResolver->addVisitor(new NameResolver());
            $statements = $nameResolver->traverse($statements);

            $checker = new ArchitectureGuardVisitor(
                $relativePath,
                $this->isFactorySource($relativePath)
            );
            $traverser = new NodeTraverser();
            $traverser->addVisitor($checker);
            $traverser->traverse($statements);

            array_push($violations, ...$checker->violations());
        }

        sort($violations);

        foreach ($violations as $violation) {
            echo 'ForbiddenCode: ' . $violation . PHP_EOL;
        }

        return $violations === [] ? 0 : 1;
    }

    /**
     * @return list<string>
     */
    private function phpFiles(string $sourceDirectory): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sourceDirectory));

        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo || $file->getExtension() !== 'php') {
                continue;
            }

            $files[] = $file->getPathname();
        }

        sort($files);

        return $files;
    }

    private function isSuppressedSource(string $relativePath): bool
    {
        foreach ($this->suppressedDirectories as $directory) {
            if ($relativePath === $directory || str_starts_with($relativePath, $directory . DIRECTORY_SEPARATOR)) {
                return true;
            }
        }

        return false;
    }

    private function isFactorySource(string $relativePath): bool
    {
        return str_contains(DIRECTORY_SEPARATOR . $relativePath, self::FACTORY_DIRECTORY);
    }

    private function relativePath(string $filePath): string
    {
        $prefix = rtrim($this->projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (str_starts_with($filePath, $prefix)) {
            return substr($filePath, strlen($prefix));
        }

        return $filePath;
    }
}

final class ArchitectureGuardVisitor extends NodeVisitorAbstract
{
    private const NATIVE_ARRAY_MESSAGE =
        'Use Psalm array shapes/docblocks or typed collections instead of native array declarations in src/.';

    /** @var list<string> */
    private array $violations = [];

    /** @var list<string> */
    private array $classStack = [];

    public function __construct(
        private readonly string $relativePath,
        private readonly bool $factorySource
    ) {
    }

    public function enterNode(Node $node): null
    {
        if ($node instanceof Node\Stmt\Class_ && $node->name !== null) {
            $this->classStack[] = $this->resolvedClassName($node);
        }

        if ($node instanceof Node\Expr\New_) {
            $this->checkNewExpression($node);
        }

        if ($node instanceof Node\FunctionLike) {
            $this->checkFunctionLike($node);
        }

        if ($node instanceof Node\Stmt\Property || $node instanceof Node\Stmt\ClassConst) {
            $this->checkTypedStatement($node);
        }

        return null;
    }

    public function leaveNode(Node $node): null
    {
        if ($node instanceof Node\Stmt\Class_ && $node->name !== null) {
            array_pop($this->classStack);
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function violations(): array
    {
        return $this->violations;
    }

    private function checkNewExpression(Node\Expr\New_ $expression): void
    {
        if ($this->factorySource) {
            return;
        }

        if ($expression->class instanceof Node\Stmt\Class_) {
            return;
        }

        if (!$expression->class instanceof Node\Name) {
            return;
        }

        $className = $this->resolvedName($expression->class);

        if ($this->shouldAllowInstantiation($className)) {
            return;
        }

        $this->violations[] = sprintf(
            'Instantiate %s via a factory or dependency injection in production code. (%s:%d)',
            $className,
            $this->relativePath,
            $expression->getStartLine()
        );
    }

    private function checkFunctionLike(Node\FunctionLike $functionLike): void
    {
        foreach ($functionLike->getParams() as $parameter) {
            if (!$this->containsNativeArrayType($parameter->type)) {
                continue;
            }

            $this->violations[] = sprintf(
                '%s (%s:%d)',
                self::NATIVE_ARRAY_MESSAGE,
                $this->relativePath,
                $parameter->getStartLine()
            );
        }

        $returnType = $functionLike->getReturnType();
        if ($returnType === null || !$this->containsNativeArrayType($returnType)) {
            return;
        }

        $this->violations[] = sprintf(
            '%s (%s:%d)',
            self::NATIVE_ARRAY_MESSAGE,
            $this->relativePath,
            $returnType->getStartLine()
        );
    }

    private function checkTypedStatement(Node\Stmt\Property|Node\Stmt\ClassConst $statement): void
    {
        $type = $statement->type;
        if ($type === null || !$this->containsNativeArrayType($type)) {
            return;
        }

        $this->violations[] = sprintf(
            '%s (%s:%d)',
            self::NATIVE_ARRAY_MESSAGE,
            $this->relativePath,
            $type->getStartLine()
        );
    }

    private function containsNativeArrayType(
        Node\Identifier|Node\Name|Node\ComplexType|null $type
    ): bool {
        if ($type === null) {
            return false;
        }

        if ($type instanceof Node\Identifier) {
            return $type->toLowerString() === 'array';
        }

        if ($type instanceof Node\UnionType || $type instanceof Node\IntersectionType) {
            foreach ($type->types as $innerType) {
                if ($this->containsNativeArrayType($innerType)) {
                    return true;
                }
            }

            return false;
        }

        if ($type instanceof Node\NullableType) {
            return $this->containsNativeArrayType($type->type);
        }

        return false;
    }

    private function shouldAllowInstantiation(string $className): bool
    {
        if ($className === '' || in_array($className, ['self', 'static', 'parent'], true)) {
            return true;
        }

        $contextSelf = end($this->classStack);

        if (
            $contextSelf === $className
            || $this->isFactoryContext($contextSelf === false ? null : $contextSelf)
            || $this->isFactoryContext($className)
        ) {
            return true;
        }

        if (preg_match('/(?:^|\\\\)[^\\\\]+(?:Exception|Error)$/', $className) === 1) {
            return true;
        }

        return preg_match('/(?:^|\\\\)(?:ArrayIterator|ArrayObject)$/', $className) === 1;
    }

    private function isFactoryContext(?string $className): bool
    {
        return $className !== null && str_contains($className, '\\Factory\\');
    }

    private function resolvedClassName(Node\Stmt\Class_ $class): string
    {
        $namespacedName = $class->getAttribute('namespacedName');

        if ($namespacedName instanceof Node\Name) {
            return $namespacedName->toString();
        }

        return $class->name?->toString() ?? '';
    }

    private function resolvedName(Node\Name $name): string
    {
        $resolvedName = $name->getAttribute('resolvedName');

        if ($resolvedName instanceof Node\Name) {
            return $resolvedName->toString();
        }

        return $name->toString();
    }
}

$guard = new ArchitectureGuard(dirname(__DIR__));

exit($guard->run());
