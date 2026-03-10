<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Extensions\IdeHelper;

use ImSuperlative\PhpstanFilament\Support\IdeHelperRegistry;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;

final class IdeHelperPropertiesExtension implements PropertiesClassReflectionExtension
{
    public function __construct(
        protected readonly IdeHelperRegistry $registry,
    ) {}

    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        if ($this->declaredInPhpDoc($classReflection, $propertyName)) {
            return false;
        }

        $modelData = $this->registry->getModelData($classReflection->getName());
        if ($modelData === null) {
            return false;
        }

        return isset($modelData->properties[$propertyName]);
    }

    public function getProperty(ClassReflection $classReflection, string $propertyName): PropertyReflection
    {
        $propertyData = $this->registry->getModelData($classReflection->getName())?->properties[$propertyName] ?? null;
        if ($propertyData === null) {
            return new IdeHelperProperty($classReflection, new MixedType, new MixedType);
        }

        $writableType = $propertyData->readOnly
            ? new NeverType
            : $propertyData->type;

        return new IdeHelperProperty($classReflection, $propertyData->type, $writableType);
    }

    protected function declaredInPhpDoc(ClassReflection $classReflection, string $propertyName): bool
    {
        return isset($classReflection->getPropertyTags()[$propertyName]);
    }
}
