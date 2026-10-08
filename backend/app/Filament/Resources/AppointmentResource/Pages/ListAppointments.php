<?php

namespace App\Filament\Resources\AppointmentResource\Pages;

use App\Filament\Pages\AppointmentCalendarPage;
use App\Filament\Resources\AppointmentResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAppointments extends ListRecords
{
    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('calendarView')
                ->label('Vista Calendario')
                ->url(AppointmentCalendarPage::getUrl())
                ->color('gray')
                ->icon('heroicon-o-calendar-days'),
        ];
    }
}
