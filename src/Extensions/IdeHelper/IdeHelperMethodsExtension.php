<?php

declare(strict_types=1);

namespace ImSuperlative\FilamentPhpstan\Extensions\IdeHelper;

use ImSuperlative\FilamentPhpstan\Data\IdeHelperParameterData;
use ImSuperlative\FilamentPhpstan\Extensions\ClosureTypeExtension\SimpleParameterReflection;
use ImSuperlative\FilamentPhpstan\Support\IdeHelperRegistry;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Type\MixedType;

final class IdeHelperMethodsExtension implements MethodsClassReflectionExtension
{
    public function __construct(
        protected readonly IdeHelperRegistry $registry,
    ) {}

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if ($classReflection->hasNativeMethod($methodName)) {
            return false;
        }

        if (isset($classReflection->getMethodTags()[$methodName])) {
            return false;
        }

        $modelData = $this->registry->getModelData($classReflection->getName());
        if ($modelData === null) {
            return false;
        }

        return isset($modelData->methods[$methodName]);
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        $methodData = $this->registry->getModelData($classReflection->getName())?->methods[$methodName] ?? null;
        if ($methodData === null) {
            return new IdeHelperMethodReflection($classReflection, $methodName, new MixedType);
        }

        $parameters = array_map(
            fn (IdeHelperParameterData $param) => new SimpleParameterReflection(
                $param->name,
                $param->type,
                $param->optional,
            ),
            $methodData->parameters,
        );

        return new IdeHelperMethodReflection(
            $classReflection,
            $methodData->name,
            $methodData->returnType,
            $methodData->isStatic,
            $parameters,
        );
    }
}
