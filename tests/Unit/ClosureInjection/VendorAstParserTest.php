<?php

use ImSuperlative\PhpstanFilament\Rules\ClosureInjection\VendorAstParser;

beforeEach(function () {
    $this->parser = new VendorAstParser;
});

it('parses ByName match arms from Component', function () {
    $filePath = realpath(__DIR__.'/../../../vendor/filament/schemas/src/Components/Component.php');
    $result = $this->parser->parseByNameMethod($filePath);

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
    $filePath = realpath(__DIR__.'/../../../vendor/filament/actions/src/Action.php');
    $result = $this->parser->parseByNameMethod($filePath);

    expect($result)
        ->toHaveKey('selectedRecords')
        ->toHaveKey('records')
        ->and($result['selectedRecords'])->toBe($result['records']);
});

it('parses ByType match arms from Component', function () {
    $filePath = realpath(__DIR__.'/../../../vendor/filament/schemas/src/Components/Component.php');
    $result = $this->parser->parseByTypeMethod($filePath);

    expect($result)
        ->toHaveKey('Filament\Schemas\Components\Utilities\Get')
        ->toHaveKey('Filament\Schemas\Components\Utilities\Set');
});

it('parses ByType match arms from Action', function () {
    $filePath = realpath(__DIR__.'/../../../vendor/filament/actions/src/Action.php');
    $result = $this->parser->parseByTypeMethod($filePath);

    expect($result)
        ->toHaveKey('Illuminate\Database\Eloquent\Builder')
        ->toHaveKey('Illuminate\Database\Eloquent\Collection');
});

it('parses evaluationIdentifier from Action', function () {
    $filePath = realpath(__DIR__.'/../../../vendor/filament/actions/src/Action.php');
    expect($this->parser->parseEvaluationIdentifier($filePath))->toBe('action');
});

it('parses evaluationIdentifier from Column', function () {
    $filePath = realpath(__DIR__.'/../../../vendor/filament/tables/src/Columns/Column.php');
    expect($this->parser->parseEvaluationIdentifier($filePath))->toBe('column');
});

it('returns null evaluationIdentifier when not set with default', function () {
    $filePath = realpath(__DIR__.'/../../../vendor/filament/support/src/Concerns/EvaluatesClosures.php');
    expect($this->parser->parseEvaluationIdentifier($filePath))->toBeNull();
});
