<?php

declare(strict_types=1);

namespace ImSuperlative\FilamentPhpstan\Support;

use ImSuperlative\FilamentPhpstan\Data\IdeHelperMethodData;
use ImSuperlative\FilamentPhpstan\Data\IdeHelperModelData;
use ImSuperlative\FilamentPhpstan\Data\IdeHelperParameterData;
use ImSuperlative\FilamentPhpstan\Data\IdeHelperPropertyData;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PHPStan\Parser\Parser;
use PHPStan\PhpDocParser\Ast\PhpDoc\MethodTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\MethodTagValueParameterNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PropertyTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VoidType;

final class IdeHelperModelParser
{
    public function __construct(
        protected readonly Parser $parser,
        protected readonly Lexer $lexer,
        protected readonly PhpDocParser $phpDocParser,
    ) {}

    /**
     * @return array<string, IdeHelperModelData> keyed by FQCN
     */
    public function parseFile(string $filePath): array
    {
        if (! file_exists($filePath)) {
            return [];
        }

        $stmts = $this->parser->parseFile($filePath);
        $results = [];

        foreach ($stmts as $stmt) {
            if (! $stmt instanceof Namespace_) {
                continue;
            }

            $namespace = $stmt->name !== null ? $stmt->name->toString() : '';
            $useImports = $this->collectUseImports($stmt);

            foreach ($stmt->stmts as $classStmt) {
                if (! $classStmt instanceof Class_ || $classStmt->name === null) {
                    continue;
                }

                $fqcn = NamespaceHelper::isRelativeNamespace($namespace)
                    ? NamespaceHelper::prependNamespace($namespace, $classStmt->name->toString())
                    : $classStmt->name->toString();
                $docComment = $classStmt->getDocComment();

                if ($docComment === null) {
                    $results[$fqcn] = new IdeHelperModelData($fqcn);

                    continue;
                }

                $phpDocNode = $this->phpDocParser->parse(
                    new TokenIterator($this->lexer->tokenize($docComment->getText()))
                );

                $properties = $this->extractProperties($phpDocNode->getPropertyTagValues(), false, $useImports, $namespace, $fqcn);
                $readOnlyProperties = $this->extractProperties($phpDocNode->getPropertyReadTagValues(), true, $useImports, $namespace, $fqcn);
                $methods = $this->extractMethods($phpDocNode->getMethodTagValues(), $useImports, $namespace, $fqcn);

                $results[$fqcn] = new IdeHelperModelData(
                    $fqcn,
                    array_merge($properties, $readOnlyProperties),
                    $methods,
                );
            }
        }

        return $results;
    }

    /**
     * @return array<string, string> short name => FQCN
     */
    protected function collectUseImports(Namespace_ $namespace): array
    {
        $imports = [];

        foreach ($namespace->stmts as $stmt) {
            if (! $stmt instanceof Use_) {
                continue;
            }

            foreach ($stmt->uses as $use) {
                $alias = $use->getAlias()->toString();
                $imports[$alias] = $use->name->toString();
            }
        }

        return $imports;
    }

    /**
     * @param  array<PropertyTagValueNode>  $tagValues
     * @param  array<string, string>  $useImports
     * @return array<string, IdeHelperPropertyData>
     */
    protected function extractProperties(array $tagValues, bool $readOnly, array $useImports, string $namespace, string $fqcn): array
    {
        $properties = [];

        foreach ($tagValues as $tag) {
            $name = ltrim($tag->propertyName, '$');
            $type = $this->resolveTypeNode($tag->type, $useImports, $namespace, $fqcn);

            $properties[$name] = new IdeHelperPropertyData($name, $type, $readOnly);
        }

        return $properties;
    }

    /**
     * @param  array<MethodTagValueNode>  $tagValues
     * @param  array<string, string>  $useImports
     * @return array<string, IdeHelperMethodData>
     */
    protected function extractMethods(array $tagValues, array $useImports, string $namespace, string $fqcn): array
    {
        $methods = [];

        foreach ($tagValues as $tag) {
            $returnType = $tag->returnType !== null
                ? $this->resolveTypeNode($tag->returnType, $useImports, $namespace, $fqcn)
                : new VoidType;

            $parameters = array_values(array_map(
                fn (MethodTagValueParameterNode $param) => $this->convertParameter($param, $useImports, $namespace, $fqcn),
                $tag->parameters,
            ));

            $methods[$tag->methodName] = new IdeHelperMethodData(
                $tag->methodName,
                $returnType,
                $tag->isStatic,
                $parameters,
            );
        }

        return $methods;
    }

    /**
     * @param  array<string, string>  $useImports
     */
    protected function convertParameter(MethodTagValueParameterNode $param, array $useImports, string $namespace, string $fqcn): IdeHelperParameterData
    {
        $type = $param->type !== null
            ? $this->resolveTypeNode($param->type, $useImports, $namespace, $fqcn)
            : new MixedType;

        $defaultValue = $param->defaultValue !== null ? (string) $param->defaultValue : null;

        return new IdeHelperParameterData(
            ltrim($param->parameterName, '$'),
            $type,
            $defaultValue !== null,
            $defaultValue,
        );
    }

    /**
     * Resolve a TypeNode AST directly into a PHPStan Type.
     *
     * @param  array<string, string>  $useImports
     */
    protected function resolveTypeNode(TypeNode $node, array $useImports, string $namespace, string $fqcn): Type
    {
        if ($node instanceof IdentifierTypeNode) {
            return $this->resolveIdentifierToType($node->name, $useImports, $namespace, $fqcn);
        }

        if ($node instanceof UnionTypeNode) {
            $types = array_map(
                fn (TypeNode $type) => $this->resolveTypeNode($type, $useImports, $namespace, $fqcn),
                $node->types,
            );

            return TypeCombinator::union(...$types);
        }

        if ($node instanceof IntersectionTypeNode) {
            return new IntersectionType(array_values(array_map(
                fn (TypeNode $type) => $this->resolveTypeNode($type, $useImports, $namespace, $fqcn),
                $node->types,
            )));
        }

        if ($node instanceof GenericTypeNode) {
            return $this->resolveIdentifierToType($node->type->name, $useImports, $namespace, $fqcn);
        }

        if ($node instanceof ArrayTypeNode) {
            return new ArrayType(new MixedType, $this->resolveTypeNode($node->type, $useImports, $namespace, $fqcn));
        }

        if ($node instanceof NullableTypeNode) {
            return TypeCombinator::addNull($this->resolveTypeNode($node->type, $useImports, $namespace, $fqcn));
        }

        if ($node instanceof ArrayShapeNode) {
            // Array shapes are complex; fall back to array type
            return new ArrayType(new MixedType, new MixedType);
        }

        return new MixedType;
    }

    /**
     * Resolve a single identifier name to a PHPStan Type.
     *
     * @param  array<string, string>  $useImports
     */
    protected function resolveIdentifierToType(string $name, array $useImports, string $namespace, string $fqcn): Type
    {
        $lower = strtolower($name);

        $builtinType = match ($lower) {
            'string' => new StringType,
            'int', 'integer' => new IntegerType,
            'float', 'double' => new FloatType,
            'bool', 'boolean' => new BooleanType,
            'array' => new ArrayType(new MixedType, new MixedType),
            'null' => new NullType,
            'void' => new VoidType,
            'true' => new ConstantBooleanType(true),
            'false' => new ConstantBooleanType(false),
            'self', 'static' => new ObjectType($fqcn),
            'mixed', 'callable', 'iterable', 'resource', 'never' => new MixedType,
            default => null,
        };

        return $builtinType ?? new ObjectType(
            $this->resolveIdentifier($name, $useImports, $namespace)
        );
    }

    /**
     * Resolve a single identifier name to its FQCN string.
     *
     * @param  array<string, string>  $useImports
     */
    protected function resolveIdentifier(string $name, array $useImports, string $namespace): string
    {
        return NamespaceHelper::toFullyQualified($name, $useImports, $namespace);
    }
}
