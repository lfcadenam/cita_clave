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
                ->label('🗓️ Vista Calendario')
                ->url(AppointmentCalendarPage::getUrl())
                ->color('rose')
                ->icon('heroicon-o-calendar-days'),

            CreateAction::make()
                ->label('Crear Cita / Reserva')
                ->modalHeading('🌸 Agendar Nueva Cita / Reserva')
                ->modalWidth(\Filament\Support\Enums\Width::SevenExtraLarge)
                ->mutateFormDataUsing(function (array $data): array {
                    $data['appointment_number'] = 'PA-' . strtoupper(now()->format('ymd')) . '-' . strtoupper(substr(uniqid(), -4));
                    return $data;
                }),
        ];
    }
}
