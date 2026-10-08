<?php

namespace App\Filament\Resources;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
use App\Enums\ServiceCategory;
use App\Filament\Resources\AppointmentResource\Pages;
use App\Models\Appointment;
use App\Models\Service;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;
    protected static bool $shouldRegisterNavigation = false;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-calendar-days';
    protected static string | UnitEnum | null $navigationGroup = 'Agenda & Citas';
    protected static ?string $modelLabel = 'Cita / Reserva';
    protected static ?string $pluralModelLabel = 'Citas y Reservas';
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', AppointmentStatus::PENDING_VERIFICATION)->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getFormComponents(): array
    {
        return [
            Grid::make(12)
                ->schema([
                    // Columna Izquierda: Clienta & Servicio (7 columnas)
                    Group::make([
                        Section::make('Datos de la Clienta')
                            ->description('Información de contacto para confirmaciones y recordatorios')
                            ->icon('heroicon-o-user')
                            ->compact()
                            ->schema([
                                Forms\Components\Select::make('existing_client_selector')
                                    ->label('Buscar Clienta Frecuente')
                                    ->placeholder('Escribe nombre o celular para autocompletar...')
                                    ->searchable()
                                    ->options(function () {
                                        return Appointment::query()
                                            ->whereNotNull('client_phone')
                                            ->where('client_phone', '!=', '')
                                            ->select('client_name', 'client_phone', 'client_email')
                                            ->distinct()
                                            ->orderBy('client_name')
                                            ->get()
                                            ->mapWithKeys(function ($item) {
                                                $email = $item->client_email ?? '';
                                                $key = "{$item->client_name}|{$item->client_phone}|{$email}";
                                                return [$key => "{$item->client_name} (+57 {$item->client_phone})"];
                                            });
                                    })
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, $set) {
                                        if ($state) {
                                            $parts = explode('|', $state);
                                            $set('client_name', $parts[0] ?? '');
                                            $set('client_phone', $parts[1] ?? '');
                                            $set('client_email', $parts[2] ?? '');
                                        }
                                    })
                                    ->dehydrated(false)
                                    ->columnSpanFull()
                                    ->helperText('Selecciona para autocompletar o ingresa los datos abajo.'),

                                Forms\Components\TextInput::make('client_name')
                                    ->label('Nombre Completo')
                                    ->placeholder('Ej: Mariana Gómez')
                                    ->required(),

                                Forms\Components\TextInput::make('client_phone')
                                    ->label('Celular WhatsApp')
                                    ->placeholder('310 123 4567')
                                    ->tel()
                                    ->prefix('+57')
                                    ->required(),

                                Forms\Components\TextInput::make('client_email')
                                    ->label('Correo Electrónico (Opcional)')
                                    ->placeholder('ejemplo@correo.com')
                                    ->email()
                                    ->columnSpanFull(),
                            ])->columns(2),

                        Section::make('Programación del Servicio')
                            ->description('Fecha, horario y tratamiento de belleza')
                            ->icon('heroicon-o-clock')
                            ->compact()
                            ->schema([
                                Forms\Components\Select::make('service_id')
                                    ->label('Servicio Solicitado')
                                    ->placeholder('Selecciona un tratamiento del catálogo...')
                                    ->options(function () {
                                        $services = Service::where('is_active', true)
                                            ->orderBy('category')
                                            ->orderBy('sort_order')
                                            ->get();

                                        $options = [];
                                        foreach ($services as $service) {
                                            $catName = $service->category instanceof ServiceCategory
                                                ? $service->category->label()
                                                : ($service->category?->value ?? 'Otros Servicios');

                                            $price = '$' . number_format($service->base_price, 0, ',', '.');
                                            $deposit = '$' . number_format($service->deposit_amount, 0, ',', '.');
                                            $duration = $service->formatted_duration;

                                            $label = "{$service->name}   ({$duration})   •   Total: {$price} COP   •   Abono: {$deposit}";

                                            $options[$catName][$service->id] = $label;
                                        }

                                        return $options;
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->reactive()
                                    ->columnSpanFull()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        if ($service = Service::find($state)) {
                                            $set('total_amount', $service->base_price);
                                            $set('deposit_amount', $service->deposit_amount);
                                            $set('balance_due', $service->base_price - $service->deposit_amount);

                                            $date = $get('appointment_date') ?: now()->toDateString();
                                            try {
                                                $availabilityService = app(\App\Services\BookingAvailabilityService::class);
                                                $slots = $availabilityService->getAvailableSlots($service, $date, false);
                                                if (!empty($slots)) {
                                                    $firstSlot = $slots[0];
                                                    $set('start_time', substr($firstSlot['start'], 0, 5));
                                                    $set('end_time', substr($firstSlot['end'], 0, 5));
                                                } else {
                                                    $targetDate = \Carbon\Carbon::parse($date);
                                                    $lastApt = Appointment::whereDate('appointment_date', $targetDate)
                                                        ->whereNotIn('status', [AppointmentStatus::CANCELLED->value])
                                                        ->orderBy('end_time', 'desc')
                                                        ->first();

                                                    if ($lastApt) {
                                                        $startTimeStr = substr($lastApt->end_time, 0, 5);
                                                        $start = \Carbon\Carbon::createFromFormat('H:i', $startTimeStr);
                                                        $set('start_time', $start->format('H:i'));
                                                        $set('end_time', $start->copy()->addMinutes($service->duration_minutes)->format('H:i'));
                                                    } else {
                                                        $schedule = \App\Models\WorkingSchedule::where('day_of_week', $targetDate->dayOfWeek)->first();
                                                        $open = ($schedule && $schedule->open_time) ? substr($schedule->open_time, 0, 5) : '08:00';
                                                        $set('start_time', $open);
                                                        $set('end_time', \Carbon\Carbon::createFromFormat('H:i', $open)->addMinutes($service->duration_minutes)->format('H:i'));
                                                    }
                                                }
                                            } catch (\Throwable $e) {
                                                $set('start_time', '08:00');
                                                $set('end_time', \Carbon\Carbon::createFromFormat('H:i', '08:00')->addMinutes($service->duration_minutes)->format('H:i'));
                                            }
                                        }
                                    }),

                                Forms\Components\DatePicker::make('appointment_date')
                                    ->label('Fecha de la Cita')
                                    ->default(now()->toDateString())
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        if ($state && ($serviceId = $get('service_id'))) {
                                            if ($service = Service::find($serviceId)) {
                                                try {
                                                    $availabilityService = app(\App\Services\BookingAvailabilityService::class);
                                                    $slots = $availabilityService->getAvailableSlots($service, $state, false);
                                                    if (!empty($slots)) {
                                                        $firstSlot = $slots[0];
                                                        $set('start_time', substr($firstSlot['start'], 0, 5));
                                                        $set('end_time', substr($firstSlot['end'], 0, 5));
                                                    }
                                                } catch (\Throwable $e) {}
                                            }
                                        }
                                    })
                                    ->columnSpanFull(),

                                Forms\Components\TimePicker::make('start_time')
                                    ->label('Hora de Inicio')
                                    ->seconds(false)
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        if ($state && ($serviceId = $get('service_id'))) {
                                            if ($service = Service::find($serviceId)) {
                                                try {
                                                    $parsed = \Carbon\Carbon::createFromFormat('H:i', substr($state, 0, 5));
                                                    $set('end_time', $parsed->addMinutes($service->duration_minutes)->format('H:i'));
                                                } catch (\Exception $e) {}
                                            }
                                        }
                                    })
                                    ->rules([
                                        function ($get, $record = null) {
                                            return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                                if (!$value) {
                                                    return;
                                                }

                                                $status = $get('status');
                                                if ($status === AppointmentStatus::CANCELLED->value || $status === AppointmentStatus::CANCELLED) {
                                                    return;
                                                }

                                                $date = $get('appointment_date');
                                                if (!$date) {
                                                    return;
                                                }

                                                $endTime = $get('end_time');
                                                if (!$endTime && ($serviceId = $get('service_id'))) {
                                                    if ($service = Service::find($serviceId)) {
                                                        try {
                                                            $endTime = \Carbon\Carbon::parse($value)->addMinutes($service->duration_minutes)->format('H:i');
                                                        } catch (\Throwable $e) {}
                                                    }
                                                }

                                                if (!$endTime) {
                                                    return;
                                                }

                                                $conflict = Appointment::findConflicting($date, $value, $endTime, $record?->id);

                                                if ($conflict) {
                                                    $conflictStart = substr($conflict->start_time, 0, 5);
                                                    $conflictEnd = substr($conflict->end_time, 0, 5);
                                                    $conflictClient = $conflict->client_name ?: 'otra clienta';
                                                    $conflictStatus = $conflict->status instanceof AppointmentStatus ? $conflict->status->label() : ($conflict->status ?? 'activa');
                                                    $fail("Horario no disponible: ya existe una cita en este horario ({$conflictStart} - {$conflictEnd}) para {$conflictClient} [{$conflictStatus}]. Por favor selecciona otro horario o fecha.");
                                                }
                                            };
                                        },
                                    ]),

                                Forms\Components\TimePicker::make('end_time')
                                    ->label('Hora de Finalización')
                                    ->seconds(false)
                                    ->required()
                                    ->rules([
                                        function ($get, $record = null) {
                                            return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                                if (!$value) {
                                                    return;
                                                }

                                                $startTime = $get('start_time');
                                                if ($startTime) {
                                                    try {
                                                        $start = \Carbon\Carbon::parse($startTime);
                                                        $end = \Carbon\Carbon::parse($value);
                                                        if ($end->lte($start)) {
                                                            $fail('La hora de finalización debe ser posterior a la hora de inicio.');
                                                            return;
                                                        }
                                                    } catch (\Throwable $e) {}
                                                }

                                                $status = $get('status');
                                                if ($status === AppointmentStatus::CANCELLED->value || $status === AppointmentStatus::CANCELLED) {
                                                    return;
                                                }

                                                $date = $get('appointment_date');
                                                if (!$date || !$startTime) {
                                                    return;
                                                }

                                                $conflict = Appointment::findConflicting($date, $startTime, $value, $record?->id);
                                                if ($conflict) {
                                                    $conflictStart = substr($conflict->start_time, 0, 5);
                                                    $conflictEnd = substr($conflict->end_time, 0, 5);
                                                    $conflictClient = $conflict->client_name ?: 'otra clienta';
                                                    $fail("Horario en conflicto: la duración seleccionada colisiona con la cita de {$conflictClient} ({$conflictStart} - {$conflictEnd}).");
                                                }
                                            };
                                        },
                                    ]),
                            ])->columns(2),
                    ])->columnSpan(7),

                    // Columna Derecha: Estado, Finanzas y Comprobante (5 columnas)
                    Group::make([
                        Section::make('Estado y Liquidación de Pagos')
                            ->description('Control financiero y abonos')
                            ->icon('heroicon-o-banknotes')
                            ->compact()
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->label('Estado de la Cita')
                                    ->options(collect(AppointmentStatus::cases())->mapWithKeys(fn ($st) => [$st->value => $st->label()]))
                                    ->default(AppointmentStatus::CONFIRMED->value)
                                    ->required(),

                                Forms\Components\Select::make('payment_method')
                                    ->label('Método de Abono')
                                    ->options(collect(PaymentMethod::cases())->mapWithKeys(fn ($pm) => [$pm->value => $pm->label()]))
                                    ->default(PaymentMethod::CASH_AT_LOCATION->value),

                                Forms\Components\TextInput::make('total_amount')
                                    ->label('Total del Servicio')
                                    ->numeric()
                                    ->prefix('$')
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        $paid = (float) ($get('deposit_paid') ?: 0);
                                        $set('balance_due', max(0, (float) $state - $paid));
                                    }),

                                Forms\Components\TextInput::make('deposit_amount')
                                    ->label('Abono Requerido')
                                    ->numeric()
                                    ->prefix('$')
                                    ->required(),

                                Forms\Components\TextInput::make('deposit_paid')
                                    ->label('Abono Recibido')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        $total = (float) ($get('total_amount') ?: 0);
                                        $set('balance_due', max(0, $total - (float) $state));
                                    }),

                                Forms\Components\TextInput::make('balance_due')
                                    ->label('Saldo Pendiente')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->extraInputAttributes(['class' => 'font-bold text-teal-700']),

                                Forms\Components\FileUpload::make('deposit_proof_image')
                                    ->label('Comprobante Nequi (Opcional)')
                                    ->image()
                                    ->directory('receipts')
                                    ->disk('public')
                                    ->visibility('public')
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('verification_notes')
                                    ->label('Notas de Verificación (Opcional)')
                                    ->placeholder('Ej: Abono registrado en caja o cuenta Nequi')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])->columns(2),
                    ])->columnSpan(5),
                ]),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components(static::getFormComponents());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('client_name')
                    ->label('Clienta')
                    ->weight('bold')
                    ->searchable(['client_name', 'client_phone', 'appointment_number'])
                    ->description(fn (Appointment $record): string => ($record->client_phone ?? '') . ($record->appointment_number ? " • #{$record->appointment_number}" : ''))
                    ->wrap(),

                TextColumn::make('service.name')
                    ->label('Servicio')
                    ->searchable()
                    ->description(fn (Appointment $record): string => $record->service?->formatted_duration ?? '')
                    ->wrap(),

                TextColumn::make('appointment_date')
                    ->label('Fecha & Hora')
                    ->date('d/m/Y')
                    ->badge()
                    ->color('primary')
                    ->description(fn (Appointment $record): string => substr($record->start_time, 0, 5) . ' - ' . substr($record->end_time, 0, 5))
                    ->grow(false),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (AppointmentStatus $state): string => $state->color())
                    ->formatStateUsing(fn (AppointmentStatus $state): string => $state->label())
                    ->description(fn (Appointment $record): ?string => $record->attendance_confirmed_at ? '✓ Asist. confirmada' : null)
                    ->grow(false),

                TextColumn::make('deposit_paid')
                    ->label('Abono Recibido')
                    ->money('COP', locale: 'es_CO')
                    ->weight('bold')
                    ->color('success')
                    ->description(fn (Appointment $record): string => "Saldo: $" . number_format($record->balance_due, 0, ',', '.'))
                    ->grow(false),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Filtrar por Estado')
                    ->options(collect(AppointmentStatus::cases())->mapWithKeys(fn ($st) => [$st->value => $st->label()])),
                SelectFilter::make('service_id')
                    ->label('Filtrar por Servicio')
                    ->relationship('service', 'name'),
            ])
            ->filtersTriggerAction(function ($action) {
                return $action
                    ->badge(fn ($table) => $table->getActiveFiltersCount() > 0 ? (string) $table->getActiveFiltersCount() : null);
            })
            ->recordAction('viewDetails')
            ->actions([
                // 1. Ver Detalle Informativo (Modal de Solo Lectura)
                Action::make('viewDetails')
                    ->label('Ver información de la cita')
                    ->tooltip('Ver detalles de la cita')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->iconButton()
                    ->size('sm')
                    ->modalHeading(fn (Appointment $record) => "Detalle de Cita — #{$record->appointment_number}")
                    ->modalContent(fn (Appointment $record) => view('admin.appointments.modal-view-details', ['appointment' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->modalWidth(\Filament\Support\Enums\Width::FiveExtraLarge),

                // 2. Botón 1 Clic: Validar Comprobante Nequi
                Action::make('verifyNequi')
                    ->label('Aprobar comprobante Nequi')
                    ->tooltip('Aprobar abono Nequi')
                    ->icon('heroicon-o-camera')
                    ->color('warning')
                    ->iconButton()
                    ->size('sm')
                    ->visible(fn (Appointment $record) => $record->status === AppointmentStatus::PENDING_VERIFICATION)
                    ->modalHeading(fn (Appointment $record) => "Validación de Transferencia Nequi — #{$record->appointment_number}")
                    ->modalContent(fn (Appointment $record) => view('admin.appointments.modal-nequi-receipt', ['appointment' => $record]))
                    ->form([
                        Forms\Components\Textarea::make('verification_notes')
                            ->label('Nota de Aprobación (Opcional)')
                            ->placeholder('Ej: Comprobante verificado en cuenta Nequi'),
                    ])
                    ->action(function (Appointment $record, array $data): void {
                        $record->update([
                            'status' => AppointmentStatus::CONFIRMED,
                            'deposit_paid' => $record->deposit_amount,
                            'balance_due' => $record->total_amount - $record->deposit_amount,
                            'verified_at' => now(),
                            'verification_notes' => $data['verification_notes'] ?? 'Comprobante aprobado por Paola',
                        ]);
                    })
                    ->modalSubmitActionLabel('Aprobar Abono y Confirmar Cita')
                    ->modalCancelActionLabel('Cerrar')
                    ->modalWidth(\Filament\Support\Enums\Width::Large),

                // 2.5. Botón Directo WhatsApp: Abrir chat con mensaje predefinido en 1 Clic
                Action::make('sendWhatsAppDirect')
                    ->label('Enviar Recordatorio WhatsApp')
                    ->tooltip('Abrir WhatsApp Web con mensaje de recordatorio y enlace')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->iconButton()
                    ->size('sm')
                    ->visible(fn (Appointment $record) => !empty($record->client_phone) && $record->status !== AppointmentStatus::CANCELLED)
                    ->url(function (Appointment $record): string {
                        $phone = preg_replace('/\D/', '', $record->client_phone);
                        if (strlen($phone) === 10 && str_starts_with($phone, '3')) {
                            $phone = '57' . $phone;
                        }
                        $clientName = explode(' ', trim($record->client_name))[0];
                        $date = \Carbon\Carbon::parse($record->appointment_date)->locale('es')->isoFormat('dddd D [de] MMMM');
                        $time = substr($record->start_time, 0, 5);
                        $balance = number_format($record->balance_due, 0, ',', '.');
                        $confirmUrl = url("/reserva/confirmar/{$record->appointment_number}");

                        $text = "✨ *¡Hola {$clientName}!* Te saludamos de *Paola Aguilera Belleza & Estética*.\n\n"
                            . "Te recordamos tu cita para el *{$date}* a las *{$time}* ({$record->service?->name}).\n"
                            . "Saldo pendiente en local: \${$balance} COP.\n\n"
                            . "¿Nos confirmas tu asistencia? Toca aquí:\n{$confirmUrl}";

                        return 'https://web.whatsapp.com/send?phone=' . $phone . '&text=' . rawurlencode($text);
                    })
                    ->openUrlInNewTab(),

                // 3. Botón: Rechazar Comprobante Nequi
                Action::make('rejectNequi')
                    ->label('Rechazar comprobante Nequi')
                    ->tooltip('Rechazar comprobante Nequi')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->iconButton()
                    ->size('sm')
                    ->visible(fn (Appointment $record) => $record->status === AppointmentStatus::PENDING_VERIFICATION)
                    ->form([
                        Forms\Components\Textarea::make('cancellation_reason')
                            ->label('Motivo del Rechazo')
                            ->placeholder('Ej: Comprobante no legible o valor inferior al abono')
                            ->required(),
                    ])
                    ->action(function (Appointment $record, array $data): void {
                        $record->update([
                            'status' => AppointmentStatus::CANCELLED,
                            'cancellation_reason' => $data['cancellation_reason'],
                            'cancelled_at' => now(),
                        ]);
                    })
                    ->modalSubmitActionLabel('Rechazar Comprobante y Cancelar Cita'),

                // 4. Marcar como completada en el local
                Action::make('complete')
                    ->label('Marcar como completada')
                    ->tooltip('Marcar como completada en el local')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->iconButton()
                    ->size('sm')
                    ->visible(fn (Appointment $record) => in_array($record->status, [AppointmentStatus::CONFIRMED, AppointmentStatus::IN_PROGRESS]))
                    ->requiresConfirmation()
                    ->modalHeading('¿Completar servicio y confirmar cobro de saldo?')
                    ->action(function (Appointment $record): void {
                        $record->update([
                            'status' => AppointmentStatus::COMPLETED,
                            'balance_due' => 0,
                        ]);
                    }),

                // 5. Edición técnica si se requiere
                EditAction::make()
                    ->label('Editar cita')
                    ->tooltip('Modificar datos de la reserva')
                    ->icon('heroicon-o-pencil-square')
                    ->color('gray')
                    ->iconButton()
                    ->size('sm')
                    ->modalHeading('Modificar Cita / Reserva')
                    ->modalWidth(\Filament\Support\Enums\Width::SevenExtraLarge),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAppointments::route('/'),
        ];
    }
}
