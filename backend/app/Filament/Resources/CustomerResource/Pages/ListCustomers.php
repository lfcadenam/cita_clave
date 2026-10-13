<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Pages\AppointmentCalendarPage;
use App\Filament\Resources\CustomerResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('+ Registrar Cliente')
                ->icon('heroicon-o-user-plus')
                ->modalHeading('Registrar Nuevo Cliente')
                ->modalWidth(Width::Large),

            /*Action::make('calendarView')
                ->label('Calendario de Citas')
                ->url(AppointmentCalendarPage::getUrl())
                ->color('gray')
                ->icon('heroicon-o-calendar-days'),*/
        ];
    }
}
