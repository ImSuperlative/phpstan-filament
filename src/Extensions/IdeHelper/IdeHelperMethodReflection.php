<?php

declare(strict_types=1);

namespace ImSuperlative\FilamentPhpstan\Extensions\IdeHelper;

use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Type;

final readonly class IdeHelperMethodReflection implements MethodReflection
{
    /** @param list<ParameterReflection> $parameters */
    public function __construct(
        private ClassReflection $classReflection,
        private string $methodName,
        private Type $returnType,
        private bool $isStaticMethod = false,
        private array $parameters = [],
    ) {}

    public function getDeclaringClass(): ClassReflection
    {
        return $this->classReflection;
    }

    public function isStatic(): bool
    {
        return $this->isStaticMethod;
    }

    public function isPrivate(): bool
    {
        return false;
    }

    public function isPublic(): bool
    {
        return true;
    }

    public function isFinal(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    public function isInternal(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    public function getDocComment(): ?string
    {
        return null;
    }

    public function getName(): string
    {
        return $this->methodName;
    }

    public function isDeprecated(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    public function getPrototype(): ClassMemberReflection
    {
        return $this;
    }

    /** @return list<FunctionVariant> */
    public function getVariants(): array
    {
        return [
            new FunctionVariant(
                TemplateTypeMap::createEmpty(),
                null,
                $this->parameters,
                false,
                $this->returnType,
            ),
        ];
    }

    public function getDeprecatedDescription(): ?string
    {
        return null;
    }

    public function getThrowType(): ?Type
    {
        return null;
    }

    public function hasSideEffects(): TrinaryLogic
    {
        return TrinaryLogic::createMaybe();
    }
}
