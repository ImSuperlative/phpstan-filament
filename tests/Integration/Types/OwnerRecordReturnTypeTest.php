<?php

it('resolves getOwnerRecord() from caller type on a RelationManager', function (string $assertionType, string $file, string $expectedType, string $actualType, int $line) {
    expect($actualType)->toBe($expectedType, basename($file).':'.$line);
})->with('owner-record-caller-rm-types');

it('resolves getOwnerRecord() from caller type on a ManageRelatedRecords page', function (string $assertionType, string $file, string $expectedType, string $actualType, int $line) {
    expect($actualType)->toBe($expectedType, basename($file).':'.$line);
})->with('owner-record-caller-manage-types');

it('resolves getOwnerRecord() from caller type in a shared class', function (string $assertionType, string $file, string $expectedType, string $actualType, int $line) {
    expect($actualType)->toBe($expectedType, basename($file).':'.$line);
})->with('shared-schema-owner-record-types')->skip('need proper test fixture');

it('resolves getOwnerRecord() from caller type ManageRelatedRecords in a shared class', function (string $assertionType, string $file, string $expectedType, string $actualType, int $line) {
    expect($actualType)->toBe($expectedType, basename($file).':'.$line);
})->with('shared-schema-owner-record-manage-types');
