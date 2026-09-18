<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlockedSlotResource\Pages;
use App\Models\BlockedSlot;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class BlockedSlotResource extends Resource
{
    protected static ?string $model = BlockedSlot::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-no-symbol';
    protected static string | UnitEnum | null $navigationGroup = 'Configuración de Horarios';
    protected static ?string $modelLabel = 'Bloqueo de Horario / Almuerzo';
    protected static ?string $pluralModelLabel = 'Bloqueos de Horario';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detalle del Bloqueo de Agenda')
                    ->description('Protege tu hora de almuerzo, citas personales o descansos')
                    ->icon('heroicon-o-lock-closed')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Título / Motivo')
                            ->placeholder('Ej: Hora de Almuerzo')
                            ->default('Hora de Almuerzo')
                            ->required(),
                        Forms\Components\DatePicker::make('blocked_date')
                            ->label('Fecha Específica (Opcional)')
                            ->helperText('Dejar vacío si aplica todos los días'),
                        Forms\Components\TimePicker::make('start_time')
                            ->label('Hora de Inicio')
                            ->seconds(false)
                            ->default('13:00')
                            ->required(),
                        Forms\Components\TimePicker::make('end_time')
                            ->label('Hora de Finalización')
                            ->seconds(false)
                            ->default('14:00')
                            ->required(),
                        Forms\Components\Toggle::make('is_recurring')
                            ->label('Repetir Diariamente (Recurrente)')
                            ->helperText('Activar para almuerzo diario de Lunes a Sábado')
                            ->default(true),
                        Forms\Components\Textarea::make('reason')
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
                TextColumn::make('title')
                    ->label('Motivo del Bloqueo')
                    ->weight('bold')
                    ->searchable(),

                TextColumn::make('blocked_date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->placeholder('Todos los días (Recurrente)')
                    ->badge()
                    ->color(fn ($state) => $state ? 'primary' : 'gray'),

                TextColumn::make('start_time')
                    ->label('Franja Bloqueada')
                    ->formatStateUsing(fn (BlockedSlot $record): string => substr($record->start_time, 0, 5) . ' - ' . substr($record->end_time, 0, 5))
                    ->badge()
                    ->color('warning'),

                IconColumn::make('is_recurring')
                    ->label('Recurrente')
                    ->boolean(),
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
            'index' => Pages\ListBlockedSlots::route('/'),
            'create' => Pages\CreateBlockedSlot::route('/create'),
            'edit' => Pages\EditBlockedSlot::route('/{record}/edit'),
        ];
    }
}
