<?php

namespace ImSuperlative\FilamentPhpstan\Extensions;

use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use ImSuperlative\FilamentPhpstan\Collectors\TableQueryRegistry;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;

/**
 * Intercepts `$table->query(Model::query())` calls during type analysis
 * and registers the resolved model in the shared TableQueryRegistry.
 *
 * Because PHPStan resolves method chains left-to-right, the registry is
 * populated before closure type extensions process column closures in
 * `->columns([...])`.
 */
final class TableQueryExtension implements DynamicMethodReturnTypeExtension
{
    public function __construct(
        protected readonly TableQueryRegistry $registry,
    ) {}

    public function getClass(): string
    {
        return Table::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'query';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): ?Type {
        $modelClass = $this->extractModelClass($methodCall, $scope);

        if ($modelClass !== null) {
            $this->registerModel($modelClass, $scope);
        }

        return null;
    }

    protected function extractModelClass(MethodCall $methodCall, Scope $scope): ?string
    {
        $args = $methodCall->getArgs();

        if ($args === []) {
            return null;
        }

        $argType = $scope->getType($args[0]->value);
        $modelClasses = $argType->getTemplateType(Builder::class, 'TModel')->getObjectClassNames();

        return $modelClasses[0] ?? null;
    }

    protected function registerModel(string $modelClass, Scope $scope): void
    {
        $classReflection = $scope->getClassReflection();
        $methodName = $scope->getFunctionName();

        if ($classReflection !== null && $methodName !== null) {
            $this->registry->register($classReflection->getName(), $methodName, $modelClass);
        }
    }
}
