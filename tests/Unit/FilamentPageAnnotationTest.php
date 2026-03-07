<?php

use ImSuperlative\FilamentPhpstan\Tests\TypeInferenceTestCase;

TypeInferenceTestCase::setConfigFiles([
    __DIR__.'/../../extension.neon',
]);

it('resolves $livewire type from @filament-page annotation', function (string $assertionType, string $file, string $expectedType, string $actualType, int $line) {
    expect($actualType)->toBe($expectedType, basename($file).':'.$line);
})->with(
    TypeInferenceTestCase::assertTypesForFile(
        __DIR__.'/../Fixtures/App/ClosureTests/FilamentPageAnnotationAssertions.php'
    )
);
