<?php

/** @noinspection ClassConstantCanBeUsedInspection */

use ImSuperlative\PhpstanFilament\Rules\ClosureInjection\VendorAstParser;
use ImSuperlative\PhpstanFilament\Tests\PhpstanTestCase;

function getVendorAstParser(): VendorAstParser
{
    return PhpstanTestCase::getContainer()->getByType(VendorAstParser::class);
}

it('parses ByName match arms from Component', function () {
    $filePath = project_root('vendor/filament/schemas/src/Components/Component.php');
    $result = getVendorAstParser()->parseByNameMethod($filePath);

    expect($result)
        ->toBeArray()
        ->toHaveKey('model', 'getModel')
        ->toHaveKey('state', 'getState')
        ->toHaveKey('record', 'getRecord')
        ->toHaveKey('livewire', 'getLivewire')
        ->toHaveKey('get', 'makeGetUtility')
        ->toHaveKey('set', 'makeSetUtility');
});

it('parses multi-key match arms from Action', function () {
    $filePath = project_root('vendor/filament/actions/src/Action.php');
    $result = getVendorAstParser()->parseByNameMethod($filePath);

    expect($result)
        ->toHaveKey('selectedRecords')
        ->toHaveKey('records')
        ->and($result['selectedRecords'])->toBe($result['records']);
});

it('parses ByType match arms from Component', function () {
    $filePath = project_root('vendor/filament/schemas/src/Components/Component.php');
    $result = getVendorAstParser()->parseByTypeMethod($filePath);

    expect($result)
        ->toHaveKey('Filament\Schemas\Components\Utilities\Get')
        ->toHaveKey('Filament\Schemas\Components\Utilities\Set');
});

it('parses ByType match arms from Action', function () {
    $filePath = project_root('vendor/filament/actions/src/Action.php');
    $result = getVendorAstParser()->parseByTypeMethod($filePath);

    expect($result)
        ->toHaveKey('Illuminate\Database\Eloquent\Builder')
        ->toHaveKey('Illuminate\Database\Eloquent\Collection');
});

it('parses evaluationIdentifier from Action', function () {
    $filePath = project_root('vendor/filament/actions/src/Action.php');
    expect(getVendorAstParser()->parseEvaluationIdentifier($filePath))->toBe('action');
});

it('parses evaluationIdentifier from Column', function () {
    $filePath = project_root('vendor/filament/tables/src/Columns/Column.php');
    expect(getVendorAstParser()->parseEvaluationIdentifier($filePath))->toBe('column');
});

it('returns null evaluationIdentifier when not set with default', function () {
    $filePath = project_root('vendor/filament/support/src/Concerns/EvaluatesClosures.php');
    expect(getVendorAstParser()->parseEvaluationIdentifier($filePath))->toBeNull();
});
