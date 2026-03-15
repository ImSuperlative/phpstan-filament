<?php

namespace Fixtures\App\Resources\Employees\Pages;

use Filament\Resources\Pages\CreateRecord;
use Fixtures\App\Resources\Employees\EmployeeResource;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;
}
