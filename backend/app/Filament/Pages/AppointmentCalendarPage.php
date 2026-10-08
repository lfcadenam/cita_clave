<?php

namespace App\Filament\Pages;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
use App\Filament\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\BlockedSlot;
use App\Models\Service;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
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

    public static function getNavigationBadge(): ?string
    {
        $count = Appointment::where('status', AppointmentStatus::PENDING_VERIFICATION)->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    protected string $view = 'filament.pages.appointment-calendar';

    public string $currentDate = '';
    public string $viewMode = 'month'; // 'month', 'week', 'day'
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

            CreateAction::make('newAppointment')
                ->model(Appointment::class)
                ->label('+ Agendar Nueva Cita')
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->modalHeading('Agendar Nueva Cita / Reserva')
                ->modalSubmitActionLabel('Crear Cita / Reserva')
                ->modalWidth(Width::SevenExtraLarge)
                ->form(AppointmentResource::getFormComponents())
                ->fillForm(function (): array {
                    $date = $this->currentDate ?: now()->toDateString();
                    $firstService = Service::where('is_active', true)->orderBy('sort_order')->first();

                    $slotData = [
                        'start_time' => '08:00',
                        'end_time' => '09:00',
                    ];

                    if ($firstService) {
                        $nextSlot = AppointmentResource::findNextAvailableSlot($firstService, $date);
                        if ($nextSlot) {
                            $slotData = $nextSlot;
                        }
                    }

                    return [
                        'appointment_date' => $date,
                        'service_id' => $firstService?->id,
                        'total_amount' => $firstService?->base_price,
                        'deposit_amount' => $firstService?->deposit_amount,
                        'balance_due' => $firstService ? ($firstService->base_price - $firstService->deposit_amount) : 0,
                        'start_time' => $slotData['start_time'],
                        'end_time' => $slotData['end_time'],
                        'status' => AppointmentStatus::CONFIRMED->value,
                        'payment_method' => PaymentMethod::CASH_AT_LOCATION->value,
                        'deposit_paid' => 0,
                    ];
                })
                ->before(function (CreateAction $action, array $data) {
                    if (!empty($data['appointment_date']) && !empty($data['start_time']) && !empty($data['end_time'])) {
                        $conflict = Appointment::findConflicting($data['appointment_date'], $data['start_time'], $data['end_time']);
                        if ($conflict) {
                            $conflictStart = substr($conflict->start_time, 0, 5);
                            $conflictEnd = substr($conflict->end_time, 0, 5);
                            Notification::make()
                                ->title('Horario no disponible')
                                ->body("Ya existe una cita programada en este horario ({$conflictStart} - {$conflictEnd}) para {$conflict->client_name}.")
                                ->danger()
                                ->send();
                            $action->halt();
                        }
                    }
                })
                ->mutateFormDataUsing(function (array $data): array {
                    $data['appointment_number'] = 'PA-' . strtoupper(now()->format('ymd')) . '-' . strtoupper(substr(uniqid(), -4));
                    if (empty($data['balance_due']) && isset($data['total_amount'])) {
                        $data['balance_due'] = (float) $data['total_amount'] - (float) ($data['deposit_paid'] ?? 0);
                    }
                    return $data;
                })
                ->successNotificationTitle('¡Cita Agendada Exitosamente!'),
        ];
    }

    public function setViewMode(string $mode): void
    {
        if (in_array($mode, ['month', 'week', 'day'])) {
            $this->viewMode = $mode;
        }
    }

    public function selectDate(string $date, string $mode = 'day'): void
    {
        $this->currentDate = $date;
        $this->viewMode = $mode;
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
            ->whereBetween('appointment_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->whereNotIn('status', [AppointmentStatus::CANCELLED->value, AppointmentStatus::CANCELLED]);

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

        // Calculate current time indicator (Red now line) in local Colombia timezone
        $now = Carbon::now(config('app.timezone', 'America/Bogota'));
        $baseHour = 7;
        $endHour = 20;
        $hourHeight = 64; // Pixeles por cada hora en la cuadrícula vertical Google Calendar
        $nowMinutes = ($now->hour * 60) + $now->minute;
        $nowTopPx = null;
        if ($nowMinutes >= ($baseHour * 60) && $nowMinutes <= ($endHour * 60)) {
            $nowTopPx = round((($nowMinutes - ($baseHour * 60)) / 60) * $hourHeight);
        }

        // Build days structure
        $days = [];
        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            $dateStr = $current->toDateString();
            $dayAppointments = $appointments->filter(fn ($apt) => $apt->appointment_date->toDateString() === $dateStr);
            $dayBlocks = $blockedSlots->filter(function ($b) use ($dateStr) {
                return $b->is_recurring || ($b->blocked_date && $b->blocked_date->toDateString() === $dateStr);
            });

            // Sort day appointments by start time, then duration descending
            $sortedApts = $dayAppointments->sortBy([
                ['start_time', 'asc'],
            ])->values();

            // Calculate overlapping groups to assign horizontal columns (like Google Calendar)
            $aptIntervals = [];
            foreach ($sortedApts as $idx => $apt) {
                $startParts = explode(':', (string) $apt->start_time);
                $startH = (int) ($startParts[0] ?? 8);
                $startM = (int) ($startParts[1] ?? 0);
                $startMinutes = ($startH * 60) + $startM;

                $endParts = explode(':', (string) $apt->end_time);
                $endH = (int) ($endParts[0] ?? ($startH + 1));
                $endM = (int) ($endParts[1] ?? 0);
                $endMinutes = ($endH * 60) + $endM;
                if ($endMinutes <= $startMinutes) {
                    $endMinutes = $startMinutes + 60;
                }

                $aptIntervals[] = [
                    'index' => $idx,
                    'start' => $startMinutes,
                    'end' => $endMinutes,
                    'col' => 0,
                    'totalCols' => 1,
                ];
            }

            // Assign columns
            $n = count($aptIntervals);
            for ($i = 0; $i < $n; $i++) {
                // Find conflicting appointments before this one
                $usedCols = [];
                for ($j = 0; $j < $i; $j++) {
                    if ($aptIntervals[$j]['end'] > $aptIntervals[$i]['start'] && $aptIntervals[$j]['start'] < $aptIntervals[$i]['end']) {
                        $usedCols[$aptIntervals[$j]['col']] = true;
                    }
                }
                // Smallest free col
                $c = 0;
                while (isset($usedCols[$c])) {
                    $c++;
                }
                $aptIntervals[$i]['col'] = $c;
            }

            // Calculate totalCols per connected overlap component
            for ($i = 0; $i < $n; $i++) {
                $maxCol = $aptIntervals[$i]['col'];
                // Check all overlapping neighbors
                for ($j = 0; $j < $n; $j++) {
                    if ($i !== $j && $aptIntervals[$j]['end'] > $aptIntervals[$i]['start'] && $aptIntervals[$j]['start'] < $aptIntervals[$i]['end']) {
                        $maxCol = max($maxCol, $aptIntervals[$j]['col']);
                    }
                }
                $aptIntervals[$i]['totalCols'] = $maxCol + 1;
            }

            // Propagate max totalCols across mutual overlaps
            for ($i = 0; $i < $n; $i++) {
                for ($j = 0; $j < $n; $j++) {
                    if ($i !== $j && $aptIntervals[$j]['end'] > $aptIntervals[$i]['start'] && $aptIntervals[$j]['start'] < $aptIntervals[$i]['end']) {
                        $maxShared = max($aptIntervals[$i]['totalCols'], $aptIntervals[$j]['totalCols']);
                        $aptIntervals[$i]['totalCols'] = $maxShared;
                        $aptIntervals[$j]['totalCols'] = $maxShared;
                    }
                }
            }

            // Map appointments with Google Calendar coordinates and columns
            $mappedAppointments = $sortedApts->map(function ($apt, $idx) use ($baseHour, $hourHeight, $aptIntervals) {
                $interval = $aptIntervals[$idx];
                $startMinutes = $interval['start'];
                $endMinutes = $interval['end'];
                $col = $interval['col'];
                $totalCols = max(1, $interval['totalCols']);

                $durationMinutes = max(30, $endMinutes - $startMinutes);
                $topMinutes = max(0, $startMinutes - ($baseHour * 60));

                $rawHeight = round(($durationMinutes / 60) * $hourHeight);
                $apt->calendar_top = round(($topMinutes / 60) * $hourHeight) + 1;
                $apt->calendar_height = max(38, $rawHeight - 3);

                // Google Calendar multi-column distribution
                $widthPct = (100 / $totalCols);
                $leftPct = ($col * $widthPct);
                $apt->calendar_left_pct = round($leftPct, 1);
                $apt->calendar_width_pct = round($widthPct, 1);
                $apt->calendar_col = $col;
                $apt->calendar_total_cols = $totalCols;

                $startCarbon = Carbon::createFromTime((int) ($startMinutes / 60), $startMinutes % 60);
                $endCarbon = Carbon::createFromTime((int) ($endMinutes / 60), $endMinutes % 60);
                $apt->formatted_time_range = $startCarbon->format('g:i A') . ' - ' . $endCarbon->format('g:i A');
                $apt->service_theme = self::getServiceTheme($apt->service, $apt->service_id);

                return $apt;
            });

            // Map blocked slots with coordinates
            $mappedBlocks = $dayBlocks->map(function ($b) use ($baseHour, $hourHeight) {
                $startParts = explode(':', (string) ($b->start_time ?: '00:00'));
                $startH = (int) ($startParts[0] ?? 8);
                $startM = (int) ($startParts[1] ?? 0);
                $startMinutes = ($startH * 60) + $startM;

                $endParts = explode(':', (string) ($b->end_time ?: '23:59'));
                $endH = (int) ($endParts[0] ?? 19);
                $endM = (int) ($endParts[1] ?? 0);
                $endMinutes = ($endH * 60) + $endM;

                $durationMinutes = max(30, $endMinutes - $startMinutes);
                $topMinutes = max(0, $startMinutes - ($baseHour * 60));

                $b->calendar_top = round(($topMinutes / 60) * $hourHeight);
                $b->calendar_height = max(36, round(($durationMinutes / 60) * $hourHeight));

                return $b;
            });

            $days[] = [
                'date' => $dateStr,
                'dayNumber' => $current->day,
                'dayName' => $current->locale('es')->isoFormat('ddd'),
                'fullDayName' => $current->locale('es')->isoFormat('dddd, D [de] MMMM'),
                'isToday' => $current->isToday(),
                'isCurrentMonth' => $current->month === $baseDate->month,
                'isSunday' => $current->isSunday(),
                'appointments' => $mappedAppointments,
                'blockedSlots' => $mappedBlocks,
            ];

            $current->addDay();
        }

        // Available hours from 07:00 to 20:00 (7 AM to 8 PM) Google Calendar style
        $hours = [];
        for ($h = $baseHour; $h <= $endHour; $h++) {
            $carbonHour = Carbon::createFromTime($h, 0);
            $hours[] = [
                'hour24' => sprintf('%02d:00', $h),
                'hourNumber' => $h,
                'label' => $carbonHour->format('g A'),
            ];
        }

        return [
            'title' => ucfirst($title),
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
            'days' => $days,
            'hours' => $hours,
            'nowTopPx' => $nowTopPx,
            'totalAppointments' => $appointments->count(),
            'confirmedCount' => $appointments->where('status', AppointmentStatus::CONFIRMED)->count(),
            'pendingCount' => $appointments->where('status', AppointmentStatus::PENDING_VERIFICATION)->count(),
            'completedCount' => $appointments->where('status', AppointmentStatus::COMPLETED)->count(),
        ];
    }

    /**
     * Retorna la paleta de color distintiva para cada categoría y servicio (Estilo Google Calendar).
     */
    public static function getServiceTheme(?Service $service, ?int $serviceId = null): array
    {
        $category = $service?->category?->value ?? ($service?->category ?? '');

        return match ($category) {
            'DEPILACION' => [
                'theme' => 'theme-amber',
                'bg' => '#fffbeb',
                'border' => '#f59e0b',
                'stripe' => '#d97706',
                'text_title' => '#92400e',
                'text_sub' => '#b45309',
                'badge_bg' => '#fef3c7',
                'badge_text' => '#78350f',
                'category_name' => 'Depilación',
            ],
            'FACIAL' => [
                'theme' => 'theme-emerald',
                'bg' => '#ecfdf5',
                'border' => '#10b981',
                'stripe' => '#059669',
                'text_title' => '#065f46',
                'text_sub' => '#047857',
                'badge_bg' => '#d1fae5',
                'badge_text' => '#064e3b',
                'category_name' => 'Facial',
            ],
            'PESTANAS_CEJAS' => [
                'theme' => 'theme-purple',
                'bg' => '#faf5ff',
                'border' => '#a855f7',
                'stripe' => '#7e22ce',
                'text_title' => '#581c87',
                'text_sub' => '#6b21a8',
                'badge_bg' => '#f3e8ff',
                'badge_text' => '#4a044e',
                'category_name' => 'Pestañas & Cejas',
            ],
            'LABIOS' => [
                'theme' => 'theme-rose',
                'bg' => '#fff1f2',
                'border' => '#f43f5e',
                'stripe' => '#e11d48',
                'text_title' => '#881337',
                'text_sub' => '#be123c',
                'badge_bg' => '#ffe4e6',
                'badge_text' => '#4c0519',
                'category_name' => 'Labios',
            ],
            'CORPORAL_MASAJES' => [
                'theme' => 'theme-cyan',
                'bg' => '#f0fdfa',
                'border' => '#06b6d4',
                'stripe' => '#0891b2',
                'text_title' => '#155e75',
                'text_sub' => '#0e7490',
                'badge_bg' => '#cffafe',
                'badge_text' => '#164e63',
                'category_name' => 'Corporal',
            ],
            default => (function () use ($serviceId, $service) {
                $id = $serviceId ?? ($service?->id ?? 1);
                $palettes = [
                    ['theme' => 'theme-blue', 'bg' => '#eff6ff', 'border' => '#3b82f6', 'stripe' => '#1d4ed8', 'text_title' => '#1e40af', 'text_sub' => '#2563eb', 'badge_bg' => '#dbeafe', 'badge_text' => '#172554', 'category_name' => 'Servicio'],
                    ['theme' => 'theme-violet', 'bg' => '#f5f3ff', 'border' => '#8b5cf6', 'stripe' => '#6d28d9', 'text_title' => '#4c1d95', 'text_sub' => '#5b21b6', 'badge_bg' => '#ede9fe', 'badge_text' => '#2e1065', 'category_name' => 'Servicio'],
                    ['theme' => 'theme-teal', 'bg' => '#f0fdfa', 'border' => '#14b8a6', 'stripe' => '#0f766e', 'text_title' => '#115e59', 'text_sub' => '#134e4a', 'badge_bg' => '#ccfbf1', 'badge_text' => '#042f2e', 'category_name' => 'Servicio'],
                    ['theme' => 'theme-orange', 'bg' => '#fff7ed', 'border' => '#f97316', 'stripe' => '#ea580c', 'text_title' => '#9a3412', 'text_sub' => '#c2410c', 'badge_bg' => '#ffedd5', 'badge_text' => '#7c2d12', 'category_name' => 'Servicio'],
                ];
                return $palettes[$id % count($palettes)];
            })(),
        };
    }
}
