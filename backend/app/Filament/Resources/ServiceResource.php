<?php

namespace App\Filament\Resources;

use App\Enums\ServiceCategory;
use App\Filament\Resources\ServiceResource\Pages;
use App\Models\Service;
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
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-sparkles';
    protected static string | UnitEnum | null $navigationGroup = 'Catálogo de Belleza';
    protected static ?string $modelLabel = 'Servicio';
    protected static ?string $pluralModelLabel = 'Servicios';
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('is_active', true)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información del Servicio de Belleza')
                    ->description('Detalles visibles para las clientas en el portal de reservas')
                    ->icon('heroicon-o-sparkles')
                    ->schema([
                        Forms\Components\Select::make('category')
                            ->label('Categoría de Belleza')
                            ->options(collect(ServiceCategory::cases())->mapWithKeys(fn ($cat) => [$cat->value => $cat->label()]))
                            ->required(),
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre del Servicio')
                            ->placeholder('Ej: Limpieza Facial Profunda con Vapor de Ozono')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('slug', \Illuminate\Support\Str::slug($state)))
                            ->required(),
                        Forms\Components\TextInput::make('slug')
                            ->label('Slug / URL amigable')
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('duration_minutes')
                            ->label('Duración del Servicio (Minutos)')
                            ->helperText('Tiempo exacto que ocupará en la agenda para evitar huecos')
                            ->numeric()
                            ->default(60)
                            ->suffix('minutos')
                            ->required(),
                        Forms\Components\TextInput::make('base_price')
                            ->label('Precio Total (COP)')
                            ->numeric()
                            ->prefix('$')
                            ->required(),
                        Forms\Components\TextInput::make('deposit_amount')
                            ->label('Monto Requerido de Abono (COP)')
                            ->helperText('Monto para apartar la cita (Bold o Nequi)')
                            ->numeric()
                            ->prefix('$')
                            ->default(30000)
                            ->required(),
                        Forms\Components\FileUpload::make('image_url')
                            ->label('Fotografía del Servicio')
                            ->image()
                            ->imageEditor()
                            ->directory('services')
                            ->disk('public')
                            ->visibility('public')
                            ->helperText('Sube una foto de referencia de alta calidad.')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('description')
                            ->label('Descripción y Beneficios')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Servicio Activo para Reservas')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_url')
                    ->label('Foto')
                    ->circular()
                    ->defaultImageUrl('https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?w=150'),

                TextColumn::make('name')
                    ->label('Servicio')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Service $record): string => $record->category instanceof ServiceCategory ? $record->category->label() : (string) $record->category),

                TextColumn::make('duration_minutes')
                    ->label('Duración')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state) => "⏱️ {$state} min"),

                TextColumn::make('base_price')
                    ->label('Precio Total')
                    ->money('COP', locale: 'es_CO')
                    ->weight('bold')
                    ->color('slate'),

                TextColumn::make('deposit_amount')
                    ->label('Abono Requerido')
                    ->money('COP', locale: 'es_CO')
                    ->weight('extrabold')
                    ->color('success'),

                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->defaultSort('sort_order', 'asc')
            ->filters([
                SelectFilter::make('category')
                    ->label('Filtrar por Categoría')
                    ->options(collect(ServiceCategory::cases())->mapWithKeys(fn ($cat) => [$cat->value => $cat->label()])),
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
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }
}
