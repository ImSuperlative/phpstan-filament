<?php

namespace ImSuperlative\PhpstanFilament\Tests\Fixtures\Stubs;

use Filament\Resources\RelationManagers\RelationManager;

class TestRelationManager extends RelationManager
{
    protected static string $relationship = 'tests';
}
