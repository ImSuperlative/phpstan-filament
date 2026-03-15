<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Scanner\Transformers\Graph\Model;

use ImSuperlative\PhpstanFilament\Data\FileMetadata;
use ImSuperlative\PhpstanFilament\Support\FilamentComponent as FC;
use ImSuperlative\PhpstanFilament\Support\NamespaceHelper;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Type\VerbosityLevel;
use ReflectionException;

trait ParsesModelFromClass
{
    protected function readStaticStringProperty(string $className, string $property): ?string
    {
        try {
            $prop = $this->reflectionProvider->getClass($className)
                ->getNativeReflection()
                ->getProperty($property)
                ->getDefaultValue();

            return is_string($prop) ? $prop : null;
        } catch (ReflectionException) {
            return null;
        }
    }

    protected function isConcreteModelClass(string $described): bool
    {
        return $described !== '' && $described !== 'object' && $described !== FC::MODEL;
    }

    protected function extractModelFromReturnType(string $className): ?string
    {
        if (! $this->isMethodDeclaredBy($className, 'getModel')) {
            return null;
        }

        $class = $this->reflectionProvider->getClass($className);

        $returnType = $class->getMethod('getModel', new OutOfClassScope)
            ->getVariants()[0]
            ->getReturnType();

        if ($returnType->isClassString()->yes()) {
            $objectType = $returnType->getClassStringObjectType();
            $described = $objectType->describe(VerbosityLevel::typeOnly());
            if ($this->isConcreteModelClass($described)) {
                return $described;
            }
        }

        $constantStrings = $returnType->getConstantStrings();
        if ($constantStrings !== []) {
            $value = $constantStrings[0]->getValue();
            if (class_exists($value)) {
                return $value;
            }
        }

        return null;
    }

    protected function extractModelFromAst(string $className, string $filePath, FileMetadata $record): ?string
    {
        if (! $this->isMethodDeclaredBy($className, 'getModel')) {
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

        return $this->extractClassFromReturn($method->stmts, $record);
    }

    protected function extractModelFromProperty(string $className): ?string
    {
        return $this->isMethodDeclaredBy($className, 'getModel')
            ? null
            : $this->readStaticStringProperty($className, 'model');
    }

    /** @param array<Stmt> $stmts */
    protected function extractClassFromReturn(array $stmts, FileMetadata $record): ?string
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

    protected function isMethodDeclaredBy(string $className, string $method): bool
    {
        $class = $this->reflectionProvider->getClass($className);

        return $class->hasMethod($method)
            && $class->getMethod($method, new OutOfClassScope)->getDeclaringClass()->getName() === $className;
    }

    /**
     * @phpstan-assert-if-true ClassConstFetch $expr
     * @phpstan-assert-if-true Identifier $expr->name
     * @phpstan-assert-if-true Name $expr->class
     */
    protected function isClassReference(Expr $expr): bool
    {
        return $expr instanceof ClassConstFetch
            && $expr->class instanceof Name
            && $expr->name instanceof Identifier
            && $expr->name->name === 'class';
    }
}
