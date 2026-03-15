<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Rules\ClosureInjection;

use Composer\Autoload\ClassLoader;
use ImSuperlative\PhpstanFilament\Support\FilamentComponent as FC;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\MixedType;
use PHPStan\Type\StaticType;
use PHPStan\Type\Type;
use ReflectionClass;

final class InjectionMapFactory
{
    protected const array METHOD_ADDITIONS = [
        'afterStateUpdated' => ['old', 'oldRaw'],
        'formatStateUsing' => ['value'],
        'dehydrateStateUsing' => ['value'],
        'mutateDehydratedStateUsing' => ['value'],
        'mutateRelationshipDataBeforeCreateUsing' => ['data', 'value'],
        'mutateRelationshipDataBeforeSaveUsing' => ['data', 'value'],
        'mutateRecordDataUsing' => ['data'],
        'disableOptionWhen' => ['value'],
        'getOptionLabelUsing' => ['value'],
        'getOptionLabelsUsing' => ['values'],
        'getSearchResultsUsing' => ['search'],
        'getOptionsSearchResultsUsing' => ['search'],
        'transformOptionsForJsUsing' => ['options'],
        'getCreateOptionUsing' => ['data'],
        'getUpdateOptionUsing' => ['data', 'record'],
        'modifyOptionsQueryUsing' => ['query'],
        'modifyQueryUsing' => ['query'],
        'relationship' => ['query'],
        'sortable' => ['query', 'direction'],
        'modifyRecordSelectOptionsQueryUsing' => ['query'],
        'modifyCreateOptionActionUsing' => ['action', 'form'],
        'modifyEditOptionActionUsing' => ['action', 'form'],
        'modifyManageOptionActionsUsing' => ['actions'],
        'modifySelectActionUsing' => ['action'],
        'modifyRecordSelectUsing' => ['select'],
        'modifyCreateAnotherActionUsing' => ['action'],
        'modifyReorderRecordsTriggerActionUsing' => ['action', 'isReordering'],
        'modifyWizardUsing' => ['wizard'],
        'modalSubmitAction' => ['action'],
        'modalCancelAction' => ['action'],
        'createOptionActionForm' => ['form', 'schema'],
        'editOptionActionForm' => ['form', 'schema'],
        'saveUploadedFileAttachmentUsing' => ['file'],
        'castStateUsing' => ['originalState', 'state'],
        'fillRecordUsing' => ['state'],
        'saveRelationshipsUsing' => ['state'],
        'resolveRelationshipUsing' => ['state'],
        'beforeReordering' => ['order'],
        'afterReordering' => ['order'],
    ];

    protected ?TypedInjectionMap $cached = null;

    public function __construct(
        protected readonly ReflectionProvider $reflectionProvider,
        protected readonly VendorAstParser $vendorAstParser,
        protected readonly DiscoveredClassCache $discoveredClassCache,
        /** @var array<string, list<string>> */
        protected readonly array $userMethodAdditions = [],
    ) {}

    public function create(): TypedInjectionMap
    {
        if ($this->cached !== null) {
            return $this->cached;
        }

        $classes = $this->discoverClasses();
        $parser = $this->vendorAstParser;

        /** @var array<class-string, list<InjectionParameter>> $classMap */
        $classMap = [];

        /** @var array<class-string, list<InjectionParameter>> $typeMap */
        $typeMap = [];

        foreach ($classes as $className => $filePath) {
            $params = $this->buildParamsForFile($filePath, $className, $parser);
            if ($params !== []) {
                $classMap[$className] = $params;
            }

            $typeEntries = $this->buildTypeMapEntries($filePath, $className, $parser);
            if ($typeEntries !== []) {
                $typeMap[$className] = $typeEntries;
            }
        }

        $methodAdditions = array_merge(self::METHOD_ADDITIONS, $this->userMethodAdditions);

        return $this->cached = new TypedInjectionMap($classMap, $typeMap, $methodAdditions, $this->reflectionProvider);
    }

    /**
     * Discover Filament classes that override resolveDefaultClosureDependencyForEvaluationByName.
     * Uses Composer's classmap for fast lookup + PHPStan reflection for method check.
     *
     * @return array<class-string, string> className => filePath
     */
    protected function discoverClasses(): array
    {
        $candidates = [];
        if ($this->discoveredClassCache->isRunningTest()) {
            $candidates = $this->discoveredClassCache->get();
        }

        if ($candidates === []) {
            $candidates = $this->scanCandidates();
        }

        return $this->filterByReflection($candidates);
    }

    /**
     * Scan vendor classmap for Filament classes whose source mentions
     * the target method. This is the expensive I/O step that the
     * test cache eliminates.
     *
     * @return array<class-string, string> className => filePath
     */
    protected function scanCandidates(): array
    {
        $vendorPath = $this->resolveVendorPath();
        $classmap = $vendorPath.'/composer/autoload_classmap.php';

        if (! file_exists($classmap)) {
            return [];
        }

        $candidates = [];
        $method = 'resolveDefaultClosureDependencyForEvaluationByName';

        foreach (require $classmap as $class => $file) {
            if (
                $class === FC::EVALUATES_CLOSURES
                || ! str_starts_with($class, FC::FILAMENT_NS)
            ) {
                continue;
            }

            if (! str_contains((string) file_get_contents($file), $method)) {
                continue;
            }

            $candidates[$class] = $file;
        }

        return $candidates;
    }

    /**
     * Filter candidates to only those that declare the method themselves.
     *
     * @param  array<class-string, string>  $candidates
     * @return array<class-string, string>
     */
    protected function filterByReflection(array $candidates): array
    {
        $classes = [];
        $method = 'resolveDefaultClosureDependencyForEvaluationByName';

        foreach ($candidates as $class => $file) {
            if (! $this->reflectionProvider->hasClass($class)) {
                continue;
            }

            $ref = $this->reflectionProvider->getClass($class);

            if (
                $ref->hasNativeMethod($method)
                && $ref->getNativeMethod($method)->getDeclaringClass()->getName() === $class
            ) {
                $classes[$class] = $file;
            }
        }

        return $classes;
    }

    protected function resolveVendorPath(): string
    {
        // ClassLoader::getRegisteredLoaders() returns loaders keyed by vendor dir.
        // When running inside phpstan.phar, the phar's loader comes first.
        // We need the project's loader — the one whose vendor dir is NOT inside a phar.
        foreach (ClassLoader::getRegisteredLoaders() as $vendorDir => $loader) {
            if (! str_starts_with($vendorDir, 'phar://')) {
                return $vendorDir;
            }
        }

        // Fallback to reflection-based approach
        $reflection = new ReflectionClass(ClassLoader::class);

        return dirname((string) $reflection->getFileName(), 2);
    }

    /** @return list<InjectionParameter> */
    protected function buildParamsForFile(string $filePath, string $className, VendorAstParser $parser): array
    {
        $byName = $parser->parseByNameMethod($filePath);
        $evalId = $parser->parseEvaluationIdentifier($filePath);

        $params = [];

        foreach ($byName as $paramName => $getterName) {
            $type = $this->resolveGetterReturnType($className, $getterName);
            $params[] = new InjectionParameter($paramName, $type);
        }

        if ($evalId !== null && $this->reflectionProvider->hasClass($className)) {
            $classReflection = $this->reflectionProvider->getClass($className);
            $params[] = new InjectionParameter($evalId, new StaticType($classReflection));
        }

        return $params;
    }

    /** @return list<InjectionParameter> */
    protected function buildTypeMapEntries(string $filePath, string $className, VendorAstParser $parser): array
    {
        $byType = $parser->parseByTypeMethod($filePath);

        $entries = [];

        foreach ($byType as $typeFqcn => $getterName) {
            // Store the FQCN as the "name" in the InjectionParameter so TypedInjectionMap
            // can use it via ObjectType($typeParam->name) for isSuperTypeOf checks.
            $entries[] = new InjectionParameter($typeFqcn, new MixedType);
        }

        return $entries;
    }

    protected function resolveGetterReturnType(string $className, string $methodName): Type
    {
        if (! $this->reflectionProvider->hasClass($className)) {
            return new MixedType;
        }

        $classReflection = $this->reflectionProvider->getClass($className);

        if (! $classReflection->hasNativeMethod($methodName)) {
            return new MixedType;
        }

        $variants = $classReflection->getNativeMethod($methodName)->getVariants();

        if ($variants === []) {
            return new MixedType;
        }

        return $variants[0]->getReturnType();
    }
}
