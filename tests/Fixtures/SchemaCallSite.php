<?php

// tests/Fixtures/SchemaCallSite.php
// This fixture is analysed by PHPStan to test the collector

namespace ImSuperlative\FilamentPhpstan\Tests\Fixtures;

use Filament\Schemas\Schema;
use ImSuperlative\FilamentPhpstan\Tests\Fixtures\Stubs\TestResource;

class TestSchemaClass
{
    public static function configure(Schema $schema): Schema
    {
        return $schema;
    }
}

class TestResourceUsingSchema extends TestResource
{
    public static function form(Schema $schema): Schema
    {
        return TestSchemaClass::configure($schema);
    }
}
