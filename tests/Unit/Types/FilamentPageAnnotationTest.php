<?php

it('resolves $livewire type from @filament-page annotation', function (string $assertionType, string $file, string $expectedType, string $actualType, int $line) {
    expect($actualType)->toBe($expectedType, basename($file).':'.$line);
})->with('filament-page-annotation-types');
