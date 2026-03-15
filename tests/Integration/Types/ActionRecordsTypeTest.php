<?php

it('types $records as Collection<int, Model> in a bulk action closure', function (string $assertionType, string $file, string $expectedType, string $actualType, int $line) {
    expect($actualType)->toBe($expectedType, basename($file).':'.$line);
})->with('action-records-types');
