<?php

namespace ImSuperlative\PhpstanFilament\Tests\Fixtures\Stubs;

use Filament\Resources\Resource;

class TestResource extends Resource
{
    protected static ?string $model = TestModel::class;
}
