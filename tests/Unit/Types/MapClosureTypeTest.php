<?php

it('infers types for map-provided params like $get, $set, $livewire, $component, $model', function (string $assertionType, string $file, string $expectedType, string $actualType, int $line) {
    expect($actualType)->toBe($expectedType, basename($file).':'.$line);
})->with('map-closure-types');
