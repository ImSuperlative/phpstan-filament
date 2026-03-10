<?php

it('types $operation as create|edit|view in a Filament component closure', function (string $assertionType, string $file, string $expectedType, string $actualType, int $line) {
    expect($actualType)->toBe($expectedType, basename($file).':'.$line);
})->with('operation-literal-types');
