<?php

namespace App\Filament\Resources\WorkingScheduleResource\Pages;

use App\Filament\Resources\WorkingScheduleResource;
use Filament\Resources\Pages\ListRecords;

class ListWorkingSchedules extends ListRecords
{
    protected static string $resource = WorkingScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
