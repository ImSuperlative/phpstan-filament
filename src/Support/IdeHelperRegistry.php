<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Support;

use ImSuperlative\PhpstanFilament\Data\IdeHelperModelData;

final class IdeHelperRegistry
{
    /** @var array<string, IdeHelperModelData>|null */
    protected ?array $models = null;

    public function __construct(
        protected readonly IdeHelperModelParser $parser,
        protected readonly bool $enabled,
        protected readonly string $customPath,
        protected readonly string $currentWorkingDirectory,
    ) {}

    public function getModelData(string $className): ?IdeHelperModelData
    {
        if (! $this->enabled) {
            return null;
        }

        $this->loadIfNeeded();

        return $this->models[$className] ?? null;
    }

    public function register(IdeHelperModelData $data): void
    {
        $this->loadIfNeeded();

        $this->models[$data->className] = $data;
    }

    protected function loadIfNeeded(): void
    {
        if ($this->models !== null) {
            return;
        }

        $this->models = [];

        if ($this->customPath !== '') {
            $this->models = $this->parser->parseFile($this->customPath);

            return;
        }

        // barryvdh first (lower priority)
        $barryvdhPath = $this->currentWorkingDirectory.'/_ide_helper_models.php';
        if (file_exists($barryvdhPath)) {
            $this->models = $this->parser->parseFile($barryvdhPath);
        }

        // Laravel Idea overwrites per-class (higher priority)
        $ideaFiles = glob($this->currentWorkingDirectory.'/vendor/_laravel_idea/_ide_helper_models_*.php');
        if ($ideaFiles !== false) {
            foreach ($ideaFiles as $file) {
                foreach ($this->parser->parseFile($file) as $className => $modelData) {
                    // Laravel Idea overwrites barryvdh per-class (higher priority)
                    $this->models[$className] = $modelData;
                }
            }
        }
    }
}
