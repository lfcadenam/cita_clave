<?php

namespace App\Filament\Pages;

use App\Enums\AppointmentStatus;
use App\Filament\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\BlockedSlot;
use App\Models\Service;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use UnitEnum;

class AppointmentCalendarPage extends Page
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-calendar-days';
    protected static string | UnitEnum | null $navigationGroup = 'Agenda & Citas';
    protected static ?string $title = 'Calendario de Citas';
    protected static ?string $navigationLabel = 'Calendario de Citas';
    protected static ?int $navigationSort = 0;
    protected static ?string $slug = 'calendario-citas';

    protected string $view = 'filament.pages.appointment-calendar';

    public string $currentDate = '';
    public string $viewMode = 'week'; // 'month', 'week', 'day'
    public string $statusFilter = 'all';
    public string $serviceFilter = 'all';
    
    public ?int $selectedAppointmentId = null;
    public bool $showDetailModal = false;
    public bool $showNequiModal = false;
    public string $verificationNotes = '';
    public string $cancellationReason = '';

    public function mount(): void
    {
        $this->currentDate = now()->toDateString();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('listView')
                ->label('Vista en Tabla / Listado')
                ->url(AppointmentResource::getUrl('index'))
                ->color('gray')
                ->icon('heroicon-o-table-cells'),

            Action::make('newAppointment')
                ->label('+ Agendar Nueva Cita')
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->modalHeading('Agendar Nueva Cita / Reserva')
                ->modalWidth(Width::SevenExtraLarge)
                ->form(AppointmentResource::getFormComponents())
                ->action(function (array $data): void {
                    $appointmentNumber = 'PA-' . strtoupper(now()->format('ymd')) . '-' . strtoupper(substr(uniqid(), -4));
                    Appointment::create(array_merge($data, [
                        'appointment_number' => $appointmentNumber,
                    ]));

                    Notification::make()
                        ->title('¡Cita Agendada!')
                        ->body("Se ha creado exitosamente la cita #{$appointmentNumber}")
                        ->success()
                        ->send();
                }),
        ];
    }

    public function setViewMode(string $mode): void
    {
        if (in_array($mode, ['month', 'week', 'day'])) {
            $this->viewMode = $mode;
        }
    }

    public function goToToday(): void
    {
        $this->currentDate = now()->toDateString();
    }

    public function previousPeriod(): void
    {
        $date = Carbon::parse($this->currentDate);
        if ($this->viewMode === 'month') {
            $this->currentDate = $date->subMonth()->startOfMonth()->toDateString();
        } elseif ($this->viewMode === 'week') {
            $this->currentDate = $date->subWeek()->startOfWeek(Carbon::MONDAY)->toDateString();
        } else {
            $this->currentDate = $date->subDay()->toDateString();
        }
    }

    public function nextPeriod(): void
    {
        $date = Carbon::parse($this->currentDate);
        if ($this->viewMode === 'month') {
            $this->currentDate = $date->addMonth()->startOfMonth()->toDateString();
        } elseif ($this->viewMode === 'week') {
            $this->currentDate = $date->addWeek()->startOfWeek(Carbon::MONDAY)->toDateString();
        } else {
            $this->currentDate = $date->addDay()->toDateString();
        }
    }

    public function selectAppointment(int $id): void
    {
        $this->selectedAppointmentId = $id;
        $this->showDetailModal = true;
        $this->showNequiModal = false;
    }

    public function openNequiValidation(int $id): void
    {
        $this->selectedAppointmentId = $id;
        $this->showNequiModal = true;
    }

    public function closeModal(): void
    {
        $this->showDetailModal = false;
        $this->showNequiModal = false;
        $this->selectedAppointmentId = null;
        $this->verificationNotes = '';
        $this->cancellationReason = '';
    }

    public function approveNequiDeposit(int $id): void
    {
        $appointment = Appointment::find($id);
        if ($appointment && $appointment->status === AppointmentStatus::PENDING_VERIFICATION) {
            $appointment->update([
                'status' => AppointmentStatus::CONFIRMED,
                'deposit_paid' => $appointment->deposit_amount,
                'balance_due' => $appointment->total_amount - $appointment->deposit_amount,
                'verified_at' => now(),
                'verification_notes' => !empty($this->verificationNotes) ? $this->verificationNotes : 'Aprobado desde Calendario por Paola',
            ]);

            Notification::make()
                ->title('¡Abono Nequi Aprobado!')
                ->body("La cita #{$appointment->appointment_number} de {$appointment->client_name} ha sido confirmada.")
                ->success()
                ->send();

            $this->closeModal();
        }
    }

    public function rejectNequiDeposit(int $id): void
    {
        $appointment = Appointment::find($id);
        if ($appointment && $appointment->status === AppointmentStatus::PENDING_VERIFICATION) {
            $appointment->update([
                'status' => AppointmentStatus::CANCELLED,
                'cancellation_reason' => !empty($this->cancellationReason) ? $this->cancellationReason : 'Comprobante no válido o no recibido.',
                'cancelled_at' => now(),
            ]);

            Notification::make()
                ->title('Comprobante Rechazado')
                ->body("La cita #{$appointment->appointment_number} ha sido cancelada.")
                ->warning()
                ->send();

            $this->closeModal();
        }
    }

    public function markAsCompleted(int $id): void
    {
        $appointment = Appointment::find($id);
        if ($appointment) {
            $appointment->update([
                'status' => AppointmentStatus::COMPLETED,
            ]);

            Notification::make()
                ->title('Cita Completada')
                ->body("Servicio de {$appointment->client_name} finalizado con éxito.")
                ->success()
                ->send();

            $this->closeModal();
        }
    }

    public function cancelAppointment(int $id): void
    {
        $appointment = Appointment::find($id);
        if ($appointment) {
            $appointment->update([
                'status' => AppointmentStatus::CANCELLED,
                'cancellation_reason' => !empty($this->cancellationReason) ? $this->cancellationReason : 'Cancelado desde el calendario',
                'cancelled_at' => now(),
            ]);

            Notification::make()
                ->title('Cita Cancelada')
                ->body("La cita #{$appointment->appointment_number} ha sido marcada como cancelada.")
                ->danger()
                ->send();

            $this->closeModal();
        }
    }

    public function getSelectedAppointmentProperty(): ?Appointment
    {
        if (!$this->selectedAppointmentId) {
            return null;
        }

        return Appointment::with('service')->find($this->selectedAppointmentId);
    }

    public function getServicesProperty()
    {
        return Service::where('is_active', true)->orderBy('name')->get();
    }

    public function getCalendarData(): array
    {
        $baseDate = Carbon::parse($this->currentDate);

        if ($this->viewMode === 'month') {
            $startDate = $baseDate->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
            $endDate = $baseDate->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
            $title = $baseDate->locale('es')->isoFormat('MMMM YYYY');
        } elseif ($this->viewMode === 'week') {
            $startDate = $baseDate->copy()->startOfWeek(Carbon::MONDAY);
            $endDate = $baseDate->copy()->endOfWeek(Carbon::SUNDAY);
            $title = 'Semana del ' . $startDate->locale('es')->isoFormat('D [de] MMMM') . ' al ' . $endDate->locale('es')->isoFormat('D [de] MMMM, YYYY');
        } else {
            $startDate = $baseDate->copy()->startOfDay();
            $endDate = $baseDate->copy()->endOfDay();
            $title = $baseDate->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY');
        }

        $query = Appointment::with('service')
            ->whereBetween('appointment_date', [$startDate->toDateString(), $endDate->toDateString()]);

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->serviceFilter !== 'all') {
            $query->where('service_id', $this->serviceFilter);
        }

        $appointments = $query->orderBy('start_time')->get();

        $blockedSlots = BlockedSlot::where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('blocked_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->orWhere('is_recurring', true);
        })->get();

        // Build days structure
        $days = [];
        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            $dateStr = $current->toDateString();
            $dayAppointments = $appointments->filter(fn ($apt) => $apt->appointment_date->toDateString() === $dateStr);
            $dayBlocks = $blockedSlots->filter(function ($b) use ($dateStr) {
                return $b->is_recurring || ($b->blocked_date && $b->blocked_date->toDateString() === $dateStr);
            });

            $days[] = [
                'date' => $dateStr,
                'dayNumber' => $current->day,
                'dayName' => $current->locale('es')->isoFormat('ddd'),
                'fullDayName' => $current->locale('es')->isoFormat('dddd'),
                'isToday' => $current->isToday(),
                'isCurrentMonth' => $current->month === $baseDate->month,
                'isSunday' => $current->isSunday(),
                'appointments' => $dayAppointments,
                'blockedSlots' => $dayBlocks,
            ];

            $current->addDay();
        }

        // Available hours from 08:00 to 19:00
        $hours = [];
        for ($h = 8; $h <= 19; $h++) {
            $hours[] = sprintf('%02d:00', $h);
        }

        return [
            'title' => ucfirst($title),
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
            'days' => $days,
            'hours' => $hours,
            'totalAppointments' => $appointments->count(),
            'confirmedCount' => $appointments->where('status', AppointmentStatus::CONFIRMED)->count(),
            'pendingCount' => $appointments->where('status', AppointmentStatus::PENDING_VERIFICATION)->count(),
            'completedCount' => $appointments->where('status', AppointmentStatus::COMPLETED)->count(),
        ];
    }
}
