<?php

namespace Fixtures\App\Resources\Employees\Pages;

use Filament\Resources\Pages\Page;
use Fixtures\App\Resources\Employees\EmployeeResource;

class OrganizationChart extends Page
{
    protected static string $resource = EmployeeResource::class;

    protected string $view = 'filament.admin.resources.employees.pages.organization-chart';
}
