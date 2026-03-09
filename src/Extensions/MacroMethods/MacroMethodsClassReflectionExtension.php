<?php

declare(strict_types=1);

namespace ImSuperlative\FilamentPhpstan\Extensions\MacroMethods;

use Closure;
use PHPStan\PhpDoc\StubFilesExtension;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Type\ClosureTypeFactory;
use ReflectionException;

final class MacroMethodsClassReflectionExtension implements MethodsClassReflectionExtension, StubFilesExtension
{
    private const string FILAMENT_MACROABLE = 'Filament\Support\Concerns\Macroable';

    /** @var array<string, MethodReflection> */
    private array $methods = [];

    /** @var array<string, bool> */
    private array $traitCache = [];

    public function __construct(
        private readonly ClosureTypeFactory $closureTypeFactory,
        private readonly bool $macroSupport = true,
    ) {}

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if (! $this->canResolveMacros($classReflection)) {
            return false;
        }

        $className = $classReflection->getName();
        if (! $this->hasMacro($className, $methodName)) {
            return false;
        }

        $closure = $this->findMacro($classReflection, $methodName);
        if (! $closure instanceof Closure) {
            return false;
        }

        $this->methods[$className.'-'.$methodName] = new MacroMethodReflection(
            $classReflection,
            $methodName,
            $this->closureTypeFactory->fromClosureObject($closure),
        );

        return true;
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        return $this->methods[$classReflection->getName().'-'.$methodName];
    }

    /** @return list<string> */
    public function getFiles(): array
    {
        return $this->macroSupport
            ? [__DIR__.'/../../../stubs/Macroable.stub']
            : [];
    }

    protected function hasMacro(string $className, string $methodName): bool
    {
        return is_callable([$className, 'hasMacro']) && $className::hasMacro($methodName);
    }

    protected function findMacro(ClassReflection $classReflection, string $methodName): ?Closure
    {
        try {
            $refProperty = $classReflection->getNativeReflection()->getProperty('macros');
        } catch (ReflectionException) {
            return null;
        }

        $macros = $refProperty->getValue();
        $macrosForMethod = $macros[$methodName] ?? [];

        $current = $classReflection;

        while ($current !== null) {
            if (isset($macrosForMethod[$current->getName()])) {
                return $macrosForMethod[$current->getName()];
            }

            $current = $current->getParentClass();
        }

        return null;
    }

    protected function canResolveMacros(ClassReflection $classReflection): bool
    {
        return $this->macroSupport
            && trait_exists(self::FILAMENT_MACROABLE)
            && $this->usesFilamentMacroable($classReflection);
    }

    protected function usesFilamentMacroable(ClassReflection $classReflection): bool
    {
        $className = $classReflection->getName();

        return $this->traitCache[$className] ?? ($this->traitCache[$className] = array_key_exists(
            self::FILAMENT_MACROABLE,
            $classReflection->getTraits(true),
        ));
    }
}
