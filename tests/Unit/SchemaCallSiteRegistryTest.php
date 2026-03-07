<?php

// tests/Unit/SchemaCallSiteRegistryTest.php

use ImSuperlative\FilamentPhpstan\Collectors\SchemaCallSiteRegistry;

beforeEach(function () {
    $this->registry = new SchemaCallSiteRegistry;
});

it('stores multiple models for the same schema class', function () {
    $this->registry->register('App\Schemas\SharedForm', 'App\Models\Event');
    $this->registry->register('App\Schemas\SharedForm', 'App\Models\Form');

    expect($this->registry->getModelsForClass('App\Schemas\SharedForm'))
        ->toBe(['App\Models\Event', 'App\Models\Form']);
});

it('stores multiple callers for the same schema class', function () {
    $this->registry->registerCaller('App\Schemas\SharedForm', 'App\Pages\ManageEventAuths');
    $this->registry->registerCaller('App\Schemas\SharedForm', 'App\Pages\ManageFormAuths');

    expect($this->registry->getCallersForClass('App\Schemas\SharedForm'))
        ->toBe(['App\Pages\ManageEventAuths', 'App\Pages\ManageFormAuths']);
});

it('deduplicates identical registrations', function () {
    $this->registry->register('App\Schemas\SharedForm', 'App\Models\Event');
    $this->registry->register('App\Schemas\SharedForm', 'App\Models\Event');

    expect($this->registry->getModelsForClass('App\Schemas\SharedForm'))
        ->toBe(['App\Models\Event']);
});

it('returns empty array for unknown schema class', function () {
    expect($this->registry->getModelsForClass('App\Schemas\Unknown'))->toBe([])
        ->and($this->registry->getCallersForClass('App\Schemas\Unknown'))->toBe([]);
});

// Backwards compat: single-value getter still works (returns first)
it('getModelForClass returns first registered model', function () {
    $this->registry->register('App\Schemas\SharedForm', 'App\Models\Event');
    $this->registry->register('App\Schemas\SharedForm', 'App\Models\Form');

    expect($this->registry->getModelForClass('App\Schemas\SharedForm'))
        ->toBe('App\Models\Event');
});
