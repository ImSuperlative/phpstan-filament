<?php

it('resolves $livewire type from @filament-page annotation', function (string $assertionType, string $file, string $expectedType, string $actualType, int $line) {
    expect($actualType)->toBe($expectedType, basename($file).':'.$line);
})->with('filament-page-annotation-types');

it('resolves $livewire type from @filament-page union annotation', function (string $assertionType, string $file, string $expectedType, string $actualType, int $line) {
    expect($actualType)->toBe($expectedType, basename($file).':'.$line);
})->with('filament-page-annotation-union-types');

it('resolves $livewire type from multiple @filament-page tags', function (string $assertionType, string $file, string $expectedType, string $actualType, int $line) {
    expect($actualType)->toBe($expectedType, basename($file).':'.$line);
})->with('filament-page-annotation-multi-tag-types');
