<?php

namespace App\Filament\Resources\WorkingScheduleResource\Pages;

use App\Filament\Resources\WorkingScheduleResource;
use Filament\Resources\Pages\EditRecord;

class EditWorkingSchedule extends EditRecord
{
    protected static string $resource = WorkingScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
