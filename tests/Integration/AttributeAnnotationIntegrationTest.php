<?php

it('resolves $livewire type from FilamentPage attribute', function (string $assertionType, string $file, string $expectedType, string $actualType, int $line) {
    expect($actualType)->toBe($expectedType, basename($file).':'.$line);
})->with('attribute-annotation-types');

it('resolves $livewire type from FilamentPage attribute with union', function (string $assertionType, string $file, string $expectedType, string $actualType, int $line) {
    expect($actualType)->toBe($expectedType, basename($file).':'.$line);
})->with('attribute-annotation-union-types');

it('resolves $livewire type from multiple FilamentPage attributes', function (string $assertionType, string $file, string $expectedType, string $actualType, int $line) {
    expect($actualType)->toBe($expectedType, basename($file).':'.$line);
})->with('attribute-annotation-multi-types');
