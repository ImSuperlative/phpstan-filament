<?php

it('narrows $state type from enum class and literal options', function (string $assertionType, string $file, string $expectedType, string $actualType, int $line) {
    expect($actualType)->toBe($expectedType, basename($file).':'.$line);
})->with('options-enum-narrowing-types');
