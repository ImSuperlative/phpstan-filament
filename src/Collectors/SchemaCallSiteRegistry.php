<?php

namespace ImSuperlative\FilamentPhpstan\Collectors;

final class SchemaCallSiteRegistry
{
    /** @var array<string, list<string>> schemaClass => modelClasses */
    protected array $models = [];

    /** @var array<string, list<string>> schemaClass => callerClasses */
    protected array $callers = [];

    protected bool $preScanLoaded = false;

    public function __construct(
        protected readonly ?SchemaCallSitePreScanner $preScanner = null,
    ) {}

    public function register(string $schemaClass, string $modelClass): void
    {
        if (! in_array($modelClass, $this->models[$schemaClass] ?? [], true)) {
            $this->models[$schemaClass][] = $modelClass;
        }
    }

    public function registerCaller(string $schemaClass, string $callerClass): void
    {
        if (! in_array($callerClass, $this->callers[$schemaClass] ?? [], true)) {
            $this->callers[$schemaClass][] = $callerClass;
        }
    }

    /** @return list<string> */
    public function getModelsForClass(string $className): array
    {
        $this->ensurePreScanned();

        return $this->models[$className] ?? [];
    }

    /** @return list<string> */
    public function getCallersForClass(string $className): array
    {
        $this->ensurePreScanned();

        return $this->callers[$className] ?? [];
    }

    /** Backwards compat: returns first registered model */
    public function getModelForClass(string $className): ?string
    {
        $this->ensurePreScanned();

        return $this->models[$className][0] ?? null;
    }

    /** Backwards compat: returns first registered caller */
    public function getCallerForClass(string $className): ?string
    {
        $this->ensurePreScanned();

        return $this->callers[$className][0] ?? null;
    }

    protected function ensurePreScanned(): void
    {
        if ($this->preScanLoaded || $this->preScanner === null) {
            return;
        }

        $this->preScanLoaded = true;

        foreach ($this->preScanner->getCallerMap() as $schemaClass => $callerClasses) {
            foreach ($callerClasses as $callerClass) {
                $this->registerCaller($schemaClass, $callerClass);
            }
        }
    }
}
