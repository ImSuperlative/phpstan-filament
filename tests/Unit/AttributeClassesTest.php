<?php

use ImSuperlative\FilamentPhpstan\Attributes\FilamentField;
use ImSuperlative\FilamentPhpstan\Attributes\FilamentModel;
use ImSuperlative\FilamentPhpstan\Attributes\FilamentPage;
use ImSuperlative\FilamentPhpstan\Attributes\FilamentState;

it('FilamentModel stores a single class type', function () {
    $attr = new FilamentModel('App\Models\Post');
    expect($attr->type)->toBe(['App\Models\Post']);
});

it('FilamentModel stores array of types', function () {
    $attr = new FilamentModel(['App\Models\Post', 'App\Models\Comment']);
    expect($attr->type)->toBe(['App\Models\Post', 'App\Models\Comment']);
});

it('FilamentState stores type and field', function () {
    $attr = new FilamentState('Carbon\Carbon', field: 'updated_at');
    expect($attr->type)->toBe(['Carbon\Carbon'])
        ->and($attr->field)->toBe('updated_at');
});

it('FilamentState field defaults to null', function () {
    $attr = new FilamentState('string');
    expect($attr->field)->toBeNull();
});

it('FilamentState accepts array of types', function () {
    $attr = new FilamentState(['Carbon\Carbon', 'string'], field: 'title');
    expect($attr->type)->toBe(['Carbon\Carbon', 'string']);
});

it('FilamentField stores type and field', function () {
    $attr = new FilamentField('App\Models\Email', field: 'latestEmail');
    expect($attr->type)->toBe(['App\Models\Email'])
        ->and($attr->field)->toBe('latestEmail');
});

it('FilamentField accepts array of types', function () {
    $attr = new FilamentField(['App\Models\Email', 'App\Models\Notification'], field: 'latestContact');
    expect($attr->type)->toBe(['App\Models\Email', 'App\Models\Notification']);
});

it('FilamentPage stores type and model', function () {
    $attr = new FilamentPage('App\Pages\EditPost', model: 'App\Models\Post');
    expect($attr->type)->toBe(['App\Pages\EditPost'])
        ->and($attr->model)->toBe('App\Models\Post');
});

it('FilamentPage model defaults to null', function () {
    $attr = new FilamentPage('App\Pages\EditPost');
    expect($attr->model)->toBeNull();
});

it('FilamentPage accepts array of types', function () {
    $attr = new FilamentPage(['App\Pages\EditPost', 'App\Pages\CreatePost']);
    expect($attr->type)->toBe(['App\Pages\EditPost', 'App\Pages\CreatePost']);
});
