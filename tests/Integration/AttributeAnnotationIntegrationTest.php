<?php

it('resolves $livewire type from FilamentPage attribute', function (string $assertionType, string $file, string $expectedType, string $actualType, int $line) {
    expect($actualType)->toBe($expectedType, basename($file).':'.$line);
})->with('attribute-annotation-types');