<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Scanner\Transformers;

use ImSuperlative\PhpstanFilament\Data\FileMetadata;
use ImSuperlative\PhpstanFilament\Data\Scanner\ResourceModels;
use ImSuperlative\PhpstanFilament\Scanner\GraphTransformer;
use ImSuperlative\PhpstanFilament\Scanner\ProjectScanResult;
use ImSuperlative\PhpstanFilament\Support\FilamentClassHelper;
use ImSuperlative\PhpstanFilament\Support\FileParser;
use ImSuperlative\PhpstanFilament\Support\NamespaceHelper;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\VerbosityLevel;

final class ModelTransformer implements GraphTransformer
{
    public function __construct(
        protected ReflectionProvider $reflectionProvider,
        protected FileParser $fileParser,
        protected FilamentClassHelper $filamentClassHelper,
    ) {}

    public function transform(ProjectScanResult $result): ProjectScanResult
    {
        $models = [];

        foreach ($result->roots as $filePath) {
            if (! isset($result->index[$filePath])) {
                continue;
            }

            $record = $result->index[$filePath];
            $fqcn = $record->fullyQualifiedName;

            if (! $this->reflectionProvider->hasClass($fqcn)) {
                continue;
            }

            $model = $this->resolveFromPhpStanReflection($fqcn)
                ?? $this->resolveFromAst($fqcn, $filePath, $record)
                ?? $this->resolveFromProperty($fqcn);

            if ($model !== null) {
                $models[$fqcn] = $model;
            }
        }

        return $result->set(new ResourceModels($models));
    }

    protected function resolveFromPhpStanReflection(string $className): ?string
    {
        if (! $this->declaresOwnMethod($className, 'getModel')) {
            return null;
        }

        $class = $this->reflectionProvider->getClass($className);

        $returnType = $class->getMethod('getModel', new OutOfClassScope)
            ->getVariants()[0]
            ->getReturnType();

        // class-string<Model> — use isClassString() + getClassStringObjectType() (non-deprecated API)
        // Exclude the base Eloquent Model — that is just the unresolved TModel template fallback
        if ($returnType->isClassString()->yes()) {
            $objectType = $returnType->getClassStringObjectType();
            $described = $objectType->describe(VerbosityLevel::typeOnly());
            if ($this->isConcreteModelClass($described)) {
                return $described;
            }
        }

        // Constant string return type — use getConstantStrings() (non-deprecated API)
        $constantStrings = $returnType->getConstantStrings();
        if ($constantStrings !== []) {
            $value = $constantStrings[0]->getValue();
            if (class_exists($value)) {
                return $value;
            }
        }

        return null;
    }

    protected function resolveFromAst(string $className, string $filePath, FileMetadata $record): ?string
    {
        if (! $this->declaresOwnMethod($className, 'getModel')) {
            return null;
        }

        $stmts = $this->fileParser->parseFile($filePath);
        if ($stmts === null) {
            return null;
        }

        $finder = $this->fileParser->nodeFinder();

        /** @var ClassMethod|null $method */
        $method = $finder->findFirst(
            $stmts,
            fn ($node) => $node instanceof ClassMethod && $node->name->name === 'getModel'
        );

        if ($method === null || $method->stmts === null) {
            return null;
        }

        return $this->resolveClassFromReturnStatement($method->stmts, $record);
    }

    protected function resolveFromProperty(string $className): ?string
    {
        if ($this->declaresOwnMethod($className, 'getModel')) {
            return null;
        }

        return $this->filamentClassHelper->readStaticProperty($className, 'model');
    }

    /** @param  array<Stmt>  $stmts */
    protected function resolveClassFromReturnStatement(array $stmts, FileMetadata $record): ?string
    {
        /** @var Return_|null $returnNode */
        $returnNode = $this->fileParser->nodeFinder()->findFirst($stmts, fn ($node) => $node instanceof Return_);

        if (! $returnNode instanceof Return_ || $returnNode->expr === null) {
            return null;
        }

        if (! $this->isClassReference($returnNode->expr)) {
            return null;
        }

        $shortName = (string) $returnNode->expr->class;

        // Skip self::class, static::class, parent::class
        if (in_array($shortName, ['self', 'static', 'parent'], true)) {
            return null;
        }

        return NamespaceHelper::toFullyQualified($shortName, $record->useMap, $record->namespace);
    }

    protected function isConcreteModelClass(string $described): bool
    {
        return $described !== '' && $described !== 'object' && $described !== 'Illuminate\Database\Eloquent\Model';
    }

    protected function declaresOwnMethod(string $className, string $method): bool
    {
        $class = $this->reflectionProvider->getClass($className);
        if (! $class->hasMethod($method)) {
            return false;
        }

        return $class->getMethod($method, new OutOfClassScope)
            ->getDeclaringClass()->getName() === $className;
    }

    /**
     * Check if an expression is a Foo::class constant fetch.
     * @phpstan-assert-if-true ClassConstFetch $expr
     * @phpstan-assert-if-true Identifier $expr->name
     * @phpstan-assert-if-true Name $expr->class
     */
    public function isClassReference(Expr $expr): bool
    {
        return $expr instanceof ClassConstFetch
            && $expr->class instanceof Name
            && $expr->name instanceof Identifier
            && $expr->name->name === 'class';
    }
}
