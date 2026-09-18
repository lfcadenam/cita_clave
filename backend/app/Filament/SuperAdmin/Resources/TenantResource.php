<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\TenantResource\Pages;
use App\Models\Tenant;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-building-office-2';
    protected static string | UnitEnum | null $navigationGroup = 'EMPRESAS & SALONES';
    protected static ?string $modelLabel = 'Salón / Empresa';
    protected static ?string $pluralModelLabel = 'Salones & Empresas SaaS';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identidad del Estudio / Salón')
                    ->description('Datos comerciales y de marca de la empresa.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre de la Empresa o Especialista')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('slug')
                            ->label('Slug / Identificador URL')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText('Ej: paola-aguilera, studio-glam'),

                        Forms\Components\TextInput::make('domain')
                            ->label('Dominio / Subdominio Personalizado (Opcional)')
                            ->maxLength(255)
                            ->helperText('Ej: paola.salonesgo.com'),

                        Forms\Components\TextInput::make('city')
                            ->label('Ciudad')
                            ->default('Bogotá')
                            ->maxLength(100),

                        Forms\Components\TextInput::make('address')
                            ->label('Dirección del Estudio')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->label('Teléfono de Contacto / WhatsApp')
                            ->tel()
                            ->maxLength(50),

                        Forms\Components\TextInput::make('email')
                            ->label('Correo Electrónico')
                            ->email()
                            ->maxLength(255),

                        Forms\Components\ColorPicker::make('primary_color')
                            ->label('Color de Marca Primario')
                            ->default('#0d9488'),
                    ])->columns(2),

                Section::make('Cobros Nequi Directo')
                    ->description('Configuración de cuenta receptora de abonos para este salón.')
                    ->schema([
                        Forms\Components\TextInput::make('nequi_phone')
                            ->label('Número de Celular Nequi')
                            ->tel()
                            ->maxLength(50),

                        Forms\Components\TextInput::make('nequi_account_holder')
                            ->label('Titular de la Cuenta Nequi')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('nequi_account_type')
                            ->label('Tipo de Cuenta')
                            ->default('Personal')
                            ->maxLength(50),
                    ])->columns(3),

                Section::make('Pasarela Bold Online')
                    ->description('Credenciales de integración de pasarela de pagos.')
                    ->schema([
                        Forms\Components\TextInput::make('bold_api_key')
                            ->label('Bold API Key')
                            ->password()
                            ->revealable()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('bold_secret_key')
                            ->label('Bold Secret Key')
                            ->password()
                            ->revealable()
                            ->maxLength(255),
                    ])->columns(2),

                Section::make('Suscripción & Plan SaaS')
                    ->description('Control de estado comercial y cuotas en la plataforma Nuvex.')
                    ->schema([
                        Forms\Components\Select::make('plan_name')
                            ->label('Plan de Suscripción')
                            ->options([
                                'Starter Salon' => 'Starter Salon (Hasta 100 citas/mes)',
                                'Pro Salon' => 'Pro Salon (Hasta 500 citas/mes)',
                                'SaaS Enterprise Nuvex' => 'SaaS Enterprise Nuvex (Ilimitado)',
                            ])
                            ->default('Pro Salon')
                            ->required(),

                        Forms\Components\Select::make('subscription_status')
                            ->label('Estado de Suscripción')
                            ->options([
                                'active' => 'Activo',
                                'trial' => 'Prueba Gratuita',
                                'suspended' => 'Suspendido',
                                'cancelled' => 'Cancelado',
                            ])
                            ->default('active')
                            ->required(),

                        Forms\Components\TextInput::make('max_appointments_per_month')
                            ->label('Límite de Citas Mensuales')
                            ->numeric()
                            ->default(500)
                            ->required(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Salón Activo en Plataforma')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre del Salón')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('city')
                    ->label('Ciudad')
                    ->sortable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Teléfono'),

                Tables\Columns\TextColumn::make('plan_name')
                    ->label('Plan SaaS')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('subscription_status')
                    ->label('Suscripción')
                    ->badge()
                    ->colors([
                        'success' => 'active',
                        'warning' => 'trial',
                        'danger' => ['suspended', 'cancelled'],
                    ]),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('subscription_status')
                    ->label('Estado Suscripción')
                    ->options([
                        'active' => 'Activo',
                        'trial' => 'Prueba',
                        'suspended' => 'Suspendido',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Salones Activos'),
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

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}