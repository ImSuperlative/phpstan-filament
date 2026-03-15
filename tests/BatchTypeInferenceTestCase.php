<?php

namespace ImSuperlative\PhpstanFilament\Tests;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\File\FileHelper;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\VerbosityLevel;

class BatchTypeInferenceTestCase extends TypeInferenceTestCase
{
    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__.'/../extension.neon',
            __DIR__.'/phpstan-test-services.neon',
        ];
    }

    /**
     * Batch-analyse multiple fixture files with a single NodeScopeResolver.
     * Returns results keyed by basename for use in Pest datasets.
     *
     * @param  list<string>  $files
     * @return array<string, list<array{0: string, 1: string, 2: mixed}>>
     */
    public static function batchGatherAssertTypes(array $files): array
    {
        set_error_handler(static fn (int $errno) => $errno === E_DEPRECATED);

        try {
            return self::analyseFiles($files);
        } finally {
            restore_error_handler();
        }
    }

    /** @return array<string, list<array{0: string, 1: string, 2: mixed}>> */
    protected static function analyseFiles(array $files): array
    {
        $fileHelper = self::getContainer()->getByType(FileHelper::class);
        $normalize = static fn (string $file): string => $fileHelper->normalizePath($file);

        $normalizedFiles = array_map($normalize, $files);

        $resolver = static::createNodeScopeResolver();
        $resolver->setAnalysedFiles(array_merge(
            $normalizedFiles,
            array_map($normalize, static::getAdditionalAnalysedFiles()),
        ));

        $results = [];

        foreach ($normalizedFiles as $file) {
            $asserts = self::analyseFile($resolver, $file);

            if ($asserts !== []) {
                $results[basename($file)] = $asserts;
            }
        }

        return $results;
    }

    /** @return list<array{0: string, 1: string, 2: mixed}> */
    protected static function analyseFile(NodeScopeResolver $resolver, string $file): array
    {
        $collector = new AssertCollector;

        $resolver->processNodes(
            self::getParser()->parseFile($file),
            self::createScope($file),
            static fn (Node $node, Scope $scope) => self::visitNode($node, $scope, $file, $collector),
        );

        return $collector->toArray();
    }

    protected static function visitNode(Node $node, Scope $scope, string $file, AssertCollector $collector): void
    {
        if (! $node instanceof FuncCall || ! $node->name instanceof Name) {
            return;
        }

        if ($node->name->toString() !== 'PHPStan\\Testing\\assertType') {
            return;
        }

        $expectedType = $scope->getType($node->getArgs()[0]->value);

        if (! $expectedType instanceof ConstantScalarType) {
            self::fail(sprintf(
                'Expected type must be a literal string, %s given on line %d.',
                $expectedType->describe(VerbosityLevel::precise()),
                $node->getStartLine(),
            ));
        }

        $actualType = $scope->getType($node->getArgs()[1]->value);

        $collector->add([
            'type',
            $file,
            $expectedType->getValue(),
            $actualType->describe(VerbosityLevel::precise()),
            $node->getStartLine(),
        ]);
    }
}
