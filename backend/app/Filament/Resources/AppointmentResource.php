<?php

namespace App\Filament\Resources;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
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
            Section::make('Datos de la Clienta')
                ->description('Información de contacto para confirmaciones y recordatorios')
                ->icon('heroicon-o-user')
                ->schema([
                    Forms\Components\Select::make('existing_client_selector')
                        ->label('🔍 Buscar Clienta Frecuente (Autocompletar Datos)')
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
                                    return [$key => "👤 {$item->client_name} (📞 +57 {$item->client_phone})"];
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
                        ->helperText('💡 Puedes elegir una clienta previa para autocompletar su información, o digitar una clienta nueva en los campos inferiores.'),

                    Forms\Components\TextInput::make('client_name')
                        ->label('Nombre Completo')
                        ->required(),
                    Forms\Components\TextInput::make('client_phone')
                        ->label('Celular WhatsApp')
                        ->tel()
                        ->prefix('+57')
                        ->required(),
                    Forms\Components\TextInput::make('client_email')
                        ->label('Correo Electrónico')
                        ->email(),
                ])->columns(3),

            Section::make('Programación del Servicio')
                ->description('Fecha, horario y servicio de belleza seleccionado')
                ->icon('heroicon-o-clock')
                ->schema([
                    Forms\Components\Select::make('service_id')
                        ->label('Servicio Solicitado')
                        ->placeholder('Selecciona un tratamiento del catálogo...')
                        ->options(function () {
                            return Service::where('is_active', true)
                                ->orderBy('category')
                                ->orderBy('sort_order')
                                ->get()
                                ->mapWithKeys(function (Service $service) {
                                    $price = '$' . number_format($service->base_price, 0, ',', '.');
                                    $deposit = '$' . number_format($service->deposit_amount, 0, ',', '.');
                                    $duration = $service->formatted_duration;

                                    $label = "{$service->name}  —  {$duration}  |  {$price} COP (Abono {$deposit})";
                                    return [$service->id => $label];
                                });
                        })
                        ->searchable()
                        ->preload()
                        ->required()
                        ->reactive()
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
                        }),
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
                        }),
                    Forms\Components\TimePicker::make('end_time')
                        ->label('Hora de Finalización')
                        ->seconds(false)
                        ->required(),
                ])->columns(4),

            Section::make('Estado y Liquidación de Pagos')
                ->description('Control de abonos recibidos y saldo pendiente por cobrar en el local')
                ->icon('heroicon-o-banknotes')
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
                        ->label('Total del Servicio (COP)')
                        ->numeric()
                        ->prefix('$')
                        ->required(),
                    Forms\Components\TextInput::make('deposit_amount')
                        ->label('Monto de Abono (COP)')
                        ->numeric()
                        ->prefix('$')
                        ->required(),
                    Forms\Components\TextInput::make('deposit_paid')
                        ->label('Abono Pagado (COP)')
                        ->numeric()
                        ->prefix('$')
                        ->default(0),
                    Forms\Components\TextInput::make('balance_due')
                        ->label('Saldo Restante en Local (COP)')
                        ->numeric()
                        ->prefix('$')
                        ->default(0),
                    Forms\Components\FileUpload::make('deposit_proof_image')
                        ->label('Comprobante de Transferencia Nequi')
                        ->image()
                        ->directory('receipts')
                        ->disk('public')
                        ->visibility('public')
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('verification_notes')
                        ->label('Notas de Verificación de Paola')
                        ->rows(2)
                        ->columnSpanFull(),
                ])->columns(3),
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
                TextColumn::make('appointment_number')
                    ->label('N° Cita')
                    ->weight('bold')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-calendar'),

                TextColumn::make('client_name')
                    ->label('Clienta')
                    ->weight('bold')
                    ->searchable()
                    ->description(fn (Appointment $record): string => "📞 {$record->client_phone}"),

                TextColumn::make('service.name')
                    ->label('Servicio')
                    ->searchable()
                    ->description(fn (Appointment $record): string => "⏱️ " . ($record->service?->formatted_duration ?? '')),

                TextColumn::make('appointment_date')
                    ->label('Fecha & Hora')
                    ->date('d/m/Y')
                    ->badge()
                    ->color('primary')
                    ->description(fn (Appointment $record): string => substr($record->start_time, 0, 5) . ' - ' . substr($record->end_time, 0, 5)),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (AppointmentStatus $state): string => $state->color())
                    ->formatStateUsing(fn (AppointmentStatus $state): string => $state->label()),

                TextColumn::make('deposit_paid')
                    ->label('Abono Recibido')
                    ->money('COP', locale: 'es_CO')
                    ->weight('bold')
                    ->color('success')
                    ->description(fn (Appointment $record): string => "Saldo: $" . number_format($record->balance_due, 0, ',', '.')),
            ])
            ->defaultSort('appointment_date', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Filtrar por Estado')
                    ->options(collect(AppointmentStatus::cases())->mapWithKeys(fn ($st) => [$st->value => $st->label()])),
                SelectFilter::make('service_id')
                    ->label('Filtrar por Servicio')
                    ->relationship('service', 'name'),
            ])
            ->actions([
                ActionGroup::make([
                    // Botón 1 Clic: Validar Comprobante Nequi
                    Action::make('verifyNequi')
                        ->label('Verificar Comprobante Nequi')
                        ->icon('heroicon-o-camera')
                        ->color('warning')
                        ->visible(fn (Appointment $record) => $record->status === AppointmentStatus::PENDING_VERIFICATION)
                        ->modalHeading(fn (Appointment $record) => "📸 Validación de Transferencia Nequi — #{$record->appointment_number}")
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
                        ->modalSubmitActionLabel('✓ Aprobar Abono y Confirmar Cita')
                        ->modalCancelActionLabel('Cerrar')
                        ->modalWidth(\Filament\Support\Enums\Width::Large),

                    // Botón: Rechazar Comprobante Nequi
                    Action::make('rejectNequi')
                        ->label('Rechazar Comprobante')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
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

                    // Marcar como completada en el local
                    Action::make('complete')
                        ->label('Marcar como Completada')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->visible(fn (Appointment $record) => in_array($record->status, [AppointmentStatus::CONFIRMED, AppointmentStatus::IN_PROGRESS]))
                        ->requiresConfirmation()
                        ->modalHeading('¿Completar servicio y confirmar cobro de saldo?')
                        ->action(function (Appointment $record): void {
                            $record->update([
                                'status' => AppointmentStatus::COMPLETED,
                                'balance_due' => 0,
                            ]);
                        }),

                    EditAction::make()
                        ->label('Editar Cita')
                        ->modalHeading('✏️ Modificar Cita / Reserva')
                        ->modalWidth(\Filament\Support\Enums\Width::SevenExtraLarge),
                ])
                ->label('Acciones')
                ->icon('heroicon-m-ellipsis-horizontal')
                ->button()
                ->color('primary')
                ->size('sm'),
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
