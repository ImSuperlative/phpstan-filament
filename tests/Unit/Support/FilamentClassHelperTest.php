<?php

// tests/Unit/FilamentClassHelperTest.php

use ImSuperlative\PhpstanFilament\Support\FilamentClassHelper;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;

beforeEach(function () {
    $this->helper = new FilamentClassHelper(
        PHPStanTestCase::getContainer()->getByType(ReflectionProvider::class),
    );
});

it('detects Filament resource classes', function () {
    expect($this->helper->isResourceClass('Filament\Resources\Resource'))->toBeTrue()
        ->and($this->helper->isResourceClass('App\Models\User'))->toBeFalse();
});

it('detects Filament resource page classes', function () {
    expect($this->helper->isResourcePage('Filament\Resources\Pages\EditRecord'))->toBeTrue()
        ->and($this->helper->isResourcePage('Filament\Resources\Pages\CreateRecord'))->toBeTrue()
        ->and($this->helper->isResourcePage('Filament\Resources\Pages\ListRecords'))->toBeTrue()
        ->and($this->helper->isResourcePage('App\Models\User'))->toBeFalse();
});

it('detects relation manager classes', function () {
    expect($this->helper->isRelationManager('Filament\Resources\RelationManagers\RelationManager'))->toBeTrue()
        ->and($this->helper->isRelationManager('App\Models\User'))->toBeFalse();
});

it('detects manage related records pages', function () {
    expect($this->helper->isManageRelatedRecords('Filament\Resources\Pages\ManageRelatedRecords'))->toBeTrue();
});

it('detects form field components', function () {
    expect($this->helper->isFormField('Filament\Forms\Components\TextInput'))->toBeTrue()
        ->and($this->helper->isFormField('Filament\Tables\Columns\TextColumn'))->toBeFalse();
});

it('detects table column components', function () {
    expect($this->helper->isTableColumn('Filament\Tables\Columns\TextColumn'))->toBeTrue()
        ->and($this->helper->isTableColumn('Filament\Forms\Components\TextInput'))->toBeFalse();
});
