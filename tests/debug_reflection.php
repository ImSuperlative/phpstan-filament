<?php

use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\VerbosityLevel;

require __DIR__.'/../vendor/autoload.php';

$container = PHPStanTestCase::getContainer();
$reflectionProvider = $container->getByType(ReflectionProvider::class);

$className = 'Fixtures\App\Models\Comment';
$classReflection = $reflectionProvider->getClass($className);
$scope = new OutOfClassScope;

echo "=== {$className} ===\n\n";

// Check all @property names from docblock + resolve their types via instance property
$phpDoc = $classReflection->getResolvedPhpDoc();
$propNames = [];
if ($phpDoc) {
    foreach ($phpDoc->getPropertyTags() as $name => $tag) {
        $propNames[] = $name;
    }
}

echo "--- @property resolved types ---\n";
foreach ($propNames as $name) {
    $has = $classReflection->hasInstanceProperty($name);
    echo "  \${$name}: ".($has ? 'YES' : 'NO');
    if ($has) {
        $type = $classReflection->getInstanceProperty($name, $scope)->getReadableType();
        echo ' -> '.$type->describe(VerbosityLevel::typeOnly());
    }
    echo "\n";
}

echo "\n--- Methods declared on class ---\n";
foreach ($classReflection->getNativeReflection()->getMethods() as $method) {
    if ($method->getDeclaringClass()->getName() !== $className) {
        continue;
    }
    $m = $classReflection->getMethod($method->getName(), $scope);
    $returnType = $m->getVariants()[0]->getReturnType();
    echo "  {$method->getName()}() -> {$returnType->describe(VerbosityLevel::typeOnly())}\n";
}

echo "\n--- @method return types ---\n";
if ($phpDoc) {
    foreach ($phpDoc->getMethodTags() as $name => $tag) {
        $returnType = $tag->getReturnType();
        echo "  {$name}() -> {$returnType->describe(VerbosityLevel::typeOnly())}\n";
    }
}
