<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkingScheduleResource\Pages;
use App\Models\WorkingSchedule;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
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

class WorkingScheduleResource extends Resource
{
    protected static ?string $model = WorkingSchedule::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-clock';
    protected static string | UnitEnum | null $navigationGroup = 'Configuración de Horarios';
    protected static ?string $modelLabel = 'Horario Laboral';
    protected static ?string $pluralModelLabel = 'Horarios Laborales Semanales';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Configuración de Jornada')
                    ->description('Define los horarios de apertura y cierre de tu estudio para este día de la semana')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        Forms\Components\TextInput::make('day_name')
                            ->label('Día de la Semana')
                            ->disabled()
                            ->required(),

                        Forms\Components\Toggle::make('is_working_day')
                            ->label('¿Día Laboral Activo?')
                            ->helperText('Si se desactiva, no se permitirán citas este día')
                            ->default(true),

                        Forms\Components\TimePicker::make('open_time')
                            ->label('Hora de Apertura')
                            ->seconds(false)
                            ->required(),

                        Forms\Components\TimePicker::make('close_time')
                            ->label('Hora de Cierre')
                            ->seconds(false)
                            ->required(),

                        Forms\Components\TextInput::make('slot_interval_minutes')
                            ->label('Intervalo entre Franjas (Minutos)')
                            ->numeric()
                            ->default(15)
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('day_name')
                    ->label('Día')
                    ->weight('bold'),

                IconColumn::make('is_working_day')
                    ->label('Laboral')
                    ->boolean(),

                TextColumn::make('open_time')
                    ->label('Horario de Atención')
                    ->formatStateUsing(function (WorkingSchedule $record): string {
                        if (! $record->is_working_day) {
                            return 'Cerrado / No laboral';
                        }
                        return substr($record->open_time, 0, 5) . ' - ' . substr($record->close_time, 0, 5);
                    })
                    ->badge()
                    ->color(fn (WorkingSchedule $record) => $record->is_working_day ? 'success' : 'danger'),

                TextColumn::make('slot_interval_minutes')
                    ->label('Resolución Agenda')
                    ->suffix(' min')
                    ->badge()
                    ->color('gray'),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkingSchedules::route('/'),
            'edit' => Pages\EditWorkingSchedule::route('/{record}/edit'),
        ];
    }
}
