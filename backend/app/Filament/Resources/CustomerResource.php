<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Models\Customer;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-users';
    protected static string | UnitEnum | null $navigationGroup = 'Agenda & Citas';
    protected static ?string $modelLabel = 'Cliente';
    protected static ?string $pluralModelLabel = 'Clientes';
    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $tenant = \Filament\Facades\Filament::getTenant();
        $query = static::getModel()::query();
        if ($tenant) {
            $query->where('tenant_id', $tenant->id);
        }
        $count = $query->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre Completo')
                    ->placeholder('Ej: Mariana Salazar')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('phone')
                    ->label('Celular WhatsApp')
                    ->placeholder('311 452 9810')
                    ->tel()
                    ->prefix('+57')
                    ->required()
                    ->maxLength(20)
                    ->columnSpan([
                        'default' => 2,
                        'sm' => 1,
                    ]),

                Forms\Components\TextInput::make('email')
                    ->label('Correo Electrónico (Opcional)')
                    ->placeholder('mariana@gmail.com')
                    ->email()
                    ->nullable()
                    ->maxLength(255)
                    ->columnSpan([
                        'default' => 2,
                        'sm' => 1,
                    ]),

                Forms\Components\Toggle::make('is_legacy')
                    ->label('Clienta Antigua del Salón (Histórica)')
                    ->helperText('Identifica a clientas habituales históricas previas a la digitalización.')
                    ->default(false)
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('notes')
                    ->label('Notas Internas')
                    ->placeholder('Preferencias, diseño favorito, observaciones...')
                    ->rows(2)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Customer $record): ?string => $record->email ?: 'Sin correo registrado'),

                TextColumn::make('phone')
                    ->label('WhatsApp')
                    ->searchable()
                    ->formatStateUsing(fn (Customer $record) => $record->getDisplayPhone())
                    ->url(fn (Customer $record) => $record->getWhatsAppUrl(), shouldOpenInNewTab: true)
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->iconColor('success'),

                TextColumn::make('loyalty_badge')
                    ->label('Tipo')
                    ->badge()
                    ->state(fn (Customer $record): string => $record->getLoyaltyBadge()['label'])
                    ->color(fn (Customer $record): string => $record->getLoyaltyBadge()['color'])
                    ->tooltip(fn (Customer $record): string => $record->getLoyaltyBadge()['description']),

                TextColumn::make('registered_at')
                    ->label('Fecha Registro')
                    ->sortable()
                    ->formatStateUsing(function ($state, Customer $record) {
                        if ($record->is_legacy) {
                            return 'Base Histórica';
                        }
                        return $state ? \Carbon\Carbon::parse($state)->translatedFormat('d M Y') : 'Reciente';
                    })
                    ->description(function ($state, Customer $record) {
                        if ($record->is_legacy) {
                            return 'Clienta Antigua del Salón';
                        }
                        return $state ? \Carbon\Carbon::parse($state)->diffForHumans() : null;
                    }),

                /*TextColumn::make('appointments_count')
                    ->label('Citas')
                    ->counts('appointments')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('total_spent')
                    ->label('Inversión Total')
                    ->formatStateUsing(fn (Customer $record) => '$ ' . number_format($record->total_spent, 0, ',', '.'))
                    ->weight('semibold')
                    ->color('success'),

                TextColumn::make('last_visit')
                    ->label('Última Visita')
                    ->state(function (Customer $record): string {
                        $last = $record->getLastAppointment();
                        return $last ? \Carbon\Carbon::parse($last->appointment_date)->translatedFormat('d M Y') : 'Sin visitas';
                    })
                    ->color('gray'),*/
            ])
            ->defaultSort('registered_at', 'desc')
            ->recordAction('viewServices')
            ->filters([
                Filter::make('antiguos')
                    ->label('Solo Clientas Antiguas / Recurrentes')
                    ->query(fn (Builder $query) => $query->where('is_legacy', true)->orWhereHas('appointments', '>=', 2)),

                Filter::make('nuevos')
                    ->label('Solo Clientas Nuevas')
                    ->query(fn (Builder $query) => $query->where('is_legacy', false)->has('appointments', '<=', 1)),
            ])
            ->actions([
                // 1. Ver Servicios y Estadísticas en Modal Ejecutivo
                Action::make('viewServices')
                    ->label('Ver Historial y Servicios')
                    ->tooltip('Ver detalle, estadísticas y servicios tomados')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->iconButton()
                    ->size('sm')
                    ->modalHeading(fn (Customer $record) => "Expediente del Cliente — {$record->name}")
                    ->modalContent(fn (Customer $record) => view('admin.customers.modal-customer-details', ['customer' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->modalWidth(Width::SevenExtraLarge),

                // 2. Botón Directo WhatsApp
                Action::make('sendWhatsApp')
                    ->label('Abrir WhatsApp Web')
                    ->tooltip('Abrir chat en WhatsApp con la clienta')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->iconButton()
                    ->size('sm')
                    ->visible(fn (Customer $record) => !empty($record->phone))
                    ->url(function (Customer $record): string {
                        $phone = $record->getCleanPhone();
                        if (strlen($phone) === 10 && str_starts_with($phone, '3')) {
                            $phone = '57' . $phone;
                        }
                        $name = explode(' ', trim($record->name))[0];
                        return "https://wa.me/{$phone}?text=Hola%20" . urlencode($name) . ",%20te%20escribimos%20de%20Paola%20Aguilera%20(La%20Belle%20Nails)";
                    }, shouldOpenInNewTab: true),

                // 3. Editar datos o notas
                EditAction::make()
                    ->label('Editar')
                    ->tooltip('Editar datos o notas del cliente')
                    ->icon('heroicon-o-pencil-square')
                    ->color('gray')
                    ->iconButton()
                    ->size('sm')
                    ->modalHeading('Editar Datos del Cliente')
                    ->modalWidth(Width::Large),
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
            'index' => Pages\ListCustomers::route('/'),
        ];
    }
}
