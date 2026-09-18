<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WaitlistResource\Pages;
use App\Models\Waitlist;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class WaitlistResource extends Resource
{
    protected static ?string $model = Waitlist::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-user-group';
    protected static string | UnitEnum | null $navigationGroup = 'Agenda & Citas';
    protected static ?string $modelLabel = 'Lista de Espera';
    protected static ?string $pluralModelLabel = 'Lista de Espera';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos de la Clienta en Espera')
                    ->description('Registro de interesadas cuando un día u horario ya está lleno')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Forms\Components\TextInput::make('client_name')
                            ->label('Nombre Completo')
                            ->required(),

                        Forms\Components\TextInput::make('client_phone')
                            ->label('WhatsApp / Celular')
                            ->tel()
                            ->required(),

                        Forms\Components\TextInput::make('client_email')
                            ->label('Correo Electrónico')
                            ->email(),

                        Forms\Components\Select::make('service_id')
                            ->label('Servicio Solicitado')
                            ->relationship('service', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\DatePicker::make('requested_date')
                            ->label('Fecha Deseada')
                            ->required(),

                        Forms\Components\Select::make('preferred_time_range')
                            ->label('Franja Horaria Preferida')
                            ->options([
                                'morning' => 'Mañana (8:00 AM - 1:00 PM)',
                                'afternoon' => 'Tarde (2:00 PM - 7:00 PM)',
                                'anytime' => 'Cualquier horario del día',
                            ])
                            ->default('anytime')
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('Estado en Lista de Espera')
                            ->options([
                                'waiting' => 'En Espera de Cupo',
                                'notified' => 'Notificada por WhatsApp',
                                'booked' => 'Cupo Agendado con Éxito',
                                'expired' => 'Vencida / No interesada',
                            ])
                            ->default('waiting')
                            ->required(),

                        Forms\Components\DateTimePicker::make('notified_at')
                            ->label('Fecha / Hora de Notificación')
                            ->nullable(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notas Adicionales')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('client_name')
                    ->label('Clienta')
                    ->weight('bold')
                    ->searchable(),

                TextColumn::make('client_phone')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-phone')
                    ->searchable(),

                TextColumn::make('service.name')
                    ->label('Servicio')
                    ->badge()
                    ->color('info'),

                TextColumn::make('requested_date')
                    ->label('Fecha Deseada')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('preferred_time_range')
                    ->label('Preferencia')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'morning' => 'Mañana',
                        'afternoon' => 'Tarde',
                        default => 'Cualquier Hora',
                    })
                    ->badge(),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'waiting' => 'warning',
                        'notified' => 'info',
                        'booked' => 'success',
                        'expired' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'waiting' => 'En Espera',
                        'notified' => 'Notificada',
                        'booked' => 'Agendada',
                        'expired' => 'Vencida',
                        default => $state,
                    }),
            ])
            ->actions([
                EditAction::make(),
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
            'index' => Pages\ListWaitlists::route('/'),
            'create' => Pages\CreateWaitlist::route('/create'),
            'edit' => Pages\EditWaitlist::route('/{record}/edit'),
        ];
    }
}
