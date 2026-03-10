<?php

it('types getOwnerRecord() as the resource model on a RelationManager subclass', function (string $assertionType, string $file, string $expectedType, string $actualType, int $line) {
    expect($actualType)->toBe($expectedType, basename($file).':'.$line);
})->with('owner-record-types');

it('resolves getOwnerRecord() from caller type in a shared class', function (string $assertionType, string $file, string $expectedType, string $actualType, int $line) {
    expect($actualType)->toBe($expectedType, basename($file).':'.$line);
})->with('shared-schema-owner-record-types');
