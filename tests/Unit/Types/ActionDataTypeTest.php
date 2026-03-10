<?php

it('types $data as an array shape derived from the action form fields', function (string $assertionType, string $file, string $expectedType, string $actualType, int $line) {
    expect($actualType)->toBe($expectedType, basename($file).':'.$line);
})->with('action-data-types');
