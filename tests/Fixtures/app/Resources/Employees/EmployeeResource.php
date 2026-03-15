<?php

namespace Fixtures\App\Resources\Employees;

use App\Models\Employee;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Fixtures\App\Resources\Employees\Pages\CreateEmployee;
use Fixtures\App\Resources\Employees\Pages\EditEmployee;
use Fixtures\App\Resources\Employees\Pages\ListEmployees;
use Fixtures\App\Resources\Employees\Pages\ManageEmployeeProjects;
use Fixtures\App\Resources\Employees\Pages\OrganizationChart;
use Fixtures\App\Resources\Employees\Pages\ViewEmployee;
use Fixtures\App\Resources\Employees\RelationManagers\RolesRelationManager;
use Fixtures\App\Resources\Employees\Schemas\EmployeeForm;
use Fixtures\App\Resources\Employees\Schemas\EmployeeInfolist;
use Fixtures\App\Resources\Employees\Tables\EmployeesTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'first_name';

    public static function form(Schema $schema): Schema
    {
        return EmployeeForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return EmployeeInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmployeesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            'roles' => RolesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployees::route('/'),
            'create' => CreateEmployee::route('/create'),
            'view' => ViewEmployee::route('/{record}'),
            'edit' => EditEmployee::route('/{record}/edit'),
            'projects' => ManageEmployeeProjects::route('/{record}/projects'),
            'chart' => OrganizationChart::route('/chart'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
